<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Config\Share as ShareConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\SocialLogin\Api\Data\LinkInterface;
use Softaware\SocialLogin\Api\Data\LinkInterfaceFactory;
use Softaware\SocialLogin\Api\LinkRepositoryInterface;
use Softaware\SocialLogin\Exception\SocialLoginException;
use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\Profile;
use Softaware\SocialLogin\Model\Provider\ProviderPool;

/**
 * Turns a verified provider identity into a signed-in (or newly linked) customer.
 */
class SocialAccountService
{
    public function __construct(
        private readonly AccountMatcher $matcher,
        private readonly CustomerCreator $customerCreator,
        private readonly CustomerLogin $customerLogin,
        private readonly PendingStorage $pendingStorage,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly LinkInterfaceFactory $linkFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AccountManagementInterface $accountManagement,
        private readonly AuthenticationInterface $authentication,
        private readonly CustomerSession $customerSession,
        private readonly StoreManagerInterface $storeManager,
        private readonly ShareConfig $shareConfig,
        private readonly ProviderPool $providerPool,
        private readonly Config $config,
        private readonly EmailValidator $emailValidator,
        private readonly CustomerRegistry $customerRegistry
    ) {
    }

    public function handle(Profile $profile, AuthRequest $request): Result
    {
        if ($request->intent === AuthRequest::INTENT_LINK) {
            return $this->linkToCurrentCustomer($profile, $request->customerId);
        }

        $storeId = $request->storeId;
        $websiteId = $this->getWebsiteId($storeId);
        $decision = $this->matcher->decide($profile, $websiteId, $this->config->getLinkMode($storeId));

        switch ($decision->type) {
            case Decision::LOGIN_LINKED:
                $customer = $this->customerRepository->getById($decision->customerId);
                $this->assertCanSignIn($customer, $profile, $websiteId);
                $this->touchLink($profile, $customer, $websiteId);
                $this->customerLogin->login($customer);
                return new Result(Result::LOGGED_IN, (int)$customer->getId(), false, $request->returnUrl);

            case Decision::LINK_EXISTING:
                $customer = $this->customerRepository->getById($decision->customerId);
                $this->assertCanSignIn($customer, $profile, $websiteId);
                $this->assertNoOtherIdentity((int)$customer->getId(), $profile->provider);
                $this->createLink($profile, (int)$customer->getId(), $websiteId);
                $this->touchLink($profile, $customer, $websiteId);
                $this->customerLogin->login($customer);
                return new Result(Result::LOGGED_IN, (int)$customer->getId(), false, $request->returnUrl);

            case Decision::ASK_PASSWORD:
                $this->pendingStorage->save($profile, $storeId, $request->returnUrl, $decision->customerId);
                return new Result(Result::NEEDS_PASSWORD, $decision->customerId, false, $request->returnUrl);

            case Decision::ASK_DETAILS:
                if ($profile->hasEmail()) {
                    // Only the name is missing and no account has this email: a new account would be created.
                    $this->assertRegistrationAllowed($profile, $storeId);
                }
                $this->pendingStorage->save($profile, $storeId, $request->returnUrl);
                return new Result(Result::NEEDS_DETAILS, 0, false, $request->returnUrl);

            default:
                $this->assertRegistrationAllowed($profile, $storeId);
                try {
                    return $this->createAndSignIn($profile, $storeId, $request->returnUrl);
                } catch (\Magento\Framework\Exception\InputException | \Magento\Framework\Validator\Exception $e) {
                    // E.g. a provider name that fails Magento's name validation: let the customer correct it.
                    $this->pendingStorage->save($profile, $storeId, $request->returnUrl);
                    return new Result(Result::NEEDS_DETAILS, 0, false, $request->returnUrl, $e->getMessage());
                }
        }
    }

    /**
     * Second step when the provider gave no email or name.
     */
    public function completeDetails(string $email, string $firstName, string $lastName): Result
    {
        $pending = $this->requirePending();
        /** @var Profile $profile */
        $profile = $pending['profile'];
        $storeId = $pending['store_id'];

        if (!$profile->hasEmail()) {
            $email = trim($email);
            if (!$this->emailValidator->isValid($email)) {
                throw new SocialLoginException(__('Please enter a valid email address.'));
            }
            $profile = $profile->withEmail($email, false);
        }
        $profile = $profile->withNames(
            trim($firstName) !== '' ? trim($firstName) : $profile->firstName,
            trim($lastName) !== '' ? trim($lastName) : $profile->lastName
        );
        if (trim($profile->firstName) === '' || trim($profile->lastName) === '') {
            throw new SocialLoginException(__('Please enter your first and last name.'));
        }

        $websiteId = $this->getWebsiteId($storeId);
        $existingId = $this->matcher->findCustomerId((string)$profile->email, $websiteId);
        if ($existingId !== null) {
            // An address typed by the customer is never trusted to sign in to an existing account.
            $this->pendingStorage->save($profile, $storeId, $pending['return_url'], $existingId);
            return new Result(Result::NEEDS_PASSWORD, $existingId, false, $pending['return_url']);
        }
        $this->assertRegistrationAllowed($profile, $storeId);
        $this->pendingStorage->clear();
        return $this->createAndSignIn($profile, $storeId, $pending['return_url']);
    }

    /**
     * Second step when an account with the same email exists: the store password proves ownership.
     */
    public function completeWithPassword(string $password): Result
    {
        $pending = $this->requirePending();
        if ($pending['customer_id'] <= 0) {
            throw new SocialLoginException(__('Your sign-in session has expired. Please try again.'));
        }
        $customer = $this->customerRepository->getById($pending['customer_id']);
        // Throws InvalidEmailOrPasswordException / UserLockedException / EmailNotConfirmedException.
        $customer = $this->accountManagement->authenticate((string)$customer->getEmail(), $password);

        $profile = $pending['profile'];
        $websiteId = $this->getWebsiteId($pending['store_id']);
        $this->assertNoOtherIdentity((int)$customer->getId(), $profile->provider);
        $this->pendingStorage->clear();
        $this->createLink($profile, (int)$customer->getId(), $websiteId);
        $this->touchLink($profile, $customer, $websiteId);
        $this->customerLogin->login($customer);
        return new Result(Result::LOGGED_IN, (int)$customer->getId(), false, $pending['return_url']);
    }

    public function getPending(): ?array
    {
        return $this->pendingStorage->get();
    }

    public function cancelPending(): void
    {
        $this->pendingStorage->clear();
    }

    /**
     * True when the customer could still sign in after removing the given link.
     *
     * Links of providers that are not installed (for example after an add-on was removed) are no way to sign
     * in: they can always be removed and do not count as a remaining sign-in method.
     */
    public function canUnlink(int $customerId, string $provider, bool $hasPassword): bool
    {
        if ($hasPassword || $this->providerPool->get($provider) === null) {
            return true;
        }
        foreach ($this->linkRepository->getListByCustomerId($customerId) as $link) {
            if ($link->getProvider() !== $provider && $this->providerPool->get($link->getProvider()) !== null) {
                return true;
            }
        }
        return false;
    }

    private function linkToCurrentCustomer(Profile $profile, int $customerId): Result
    {
        if (!$this->customerSession->isLoggedIn() || (int)$this->customerSession->getCustomerId() !== $customerId) {
            throw new SocialLoginException(__('Please sign in to your account and try again.'));
        }
        $customer = $this->customerRepository->getById($customerId);
        $websiteId = (int)$customer->getWebsiteId();
        $providerName = $this->getProviderName($profile->provider);

        $existing = $this->linkRepository->findByProviderUser($profile->provider, $profile->providerUserId, $websiteId);
        if ($existing !== null) {
            if ($existing->getCustomerId() !== $customerId) {
                throw new SocialLoginException(
                    __('This %1 account is already connected to another customer account.', $providerName)
                );
            }
            return new Result(Result::LINKED, $customerId);
        }
        if ($this->linkRepository->findByCustomerAndProvider($customerId, $profile->provider) !== null) {
            throw new SocialLoginException(
                __('A different %1 account is already connected. Disconnect it first.', $providerName)
            );
        }
        $this->createLink($profile, $customerId, $websiteId);
        return new Result(Result::LINKED, $customerId);
    }

    private function createAndSignIn(Profile $profile, int $storeId, string $returnUrl): Result
    {
        $created = $this->customerCreator->create($profile, $storeId);
        $customer = $created['customer'];
        $this->createLink($profile, (int)$customer->getId(), (int)$customer->getWebsiteId());
        if ($created['needs_confirmation']) {
            return new Result(Result::NEEDS_CONFIRMATION, (int)$customer->getId(), true, $returnUrl);
        }
        $this->touchLink($profile, $customer, (int)$customer->getWebsiteId());
        $this->customerLogin->login($customer);
        return new Result(Result::LOGGED_IN, (int)$customer->getId(), true, $returnUrl);
    }

    private function assertCanSignIn(CustomerInterface $customer, Profile $profile, int $websiteId): void
    {
        if ($this->shareConfig->isWebsiteScope() && (int)$customer->getWebsiteId() !== $websiteId) {
            throw new SocialLoginException(__('This account cannot be used on this website.'));
        }
        if ($this->authentication->isLocked((int)$customer->getId())) {
            throw new SocialLoginException(__('Your account is temporarily locked. Please try again later.'));
        }
        if ((string)$customer->getConfirmation() === '') {
            return;
        }
        $customerId = (int)$customer->getId();
        $required = $this->accountManagement->getConfirmationStatus($customerId)
            === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED;
        if (!$required && $this->hasPassword($customerId)) {
            // The store does not require confirmation and the customer can sign in with the password anyway.
            return;
        }
        // Unconfirmed: the store requires confirmation, or the account was created from an address the
        // provider had not verified (social accounts have no password, so nothing else proves the address).
        $sameTrustedEmail = $profile->emailTrusted
            && strcasecmp((string)$profile->email, (string)$customer->getEmail()) === 0;
        if (!$sameTrustedEmail) {
            throw new SocialLoginException(
                __('This account is not confirmed yet. Please use the confirmation link we sent to your email address.')
            );
        }
        // The provider vouches for the address, which is what the confirmation email would prove.
        $customer->setConfirmation(null);
        $this->customerRepository->save($customer);
    }

    private function hasPassword(int $customerId): bool
    {
        return (string)$this->customerRegistry->retrieveSecureData($customerId)->getPasswordHash() !== '';
    }

    /**
     * "Create new accounts" is off: social sign-in only works for existing customers.
     */
    private function assertRegistrationAllowed(Profile $profile, int $storeId): void
    {
        if ($this->config->isRegistrationAllowed($storeId)) {
            return;
        }
        throw SocialLoginException::withDetail(
            $profile->hasEmail()
                ? __('There is no account with the email address %1. Please create an account first, then connect it on the "Connected Accounts" page.', $profile->email)
                : __('There is no account with this email address. Please create an account first, then connect it on the "Connected Accounts" page.'),
            sprintf('%s: no matching account and new accounts are switched off', $profile->provider)
        );
    }

    private function assertNoOtherIdentity(int $customerId, string $provider): void
    {
        if ($this->linkRepository->findByCustomerAndProvider($customerId, $provider) !== null) {
            throw new SocialLoginException(__(
                'A different %1 account is already connected to this store account. '
                . 'Sign in with that one, or with your email address and password.',
                $this->getProviderName($provider)
            ));
        }
    }

    private function createLink(Profile $profile, int $customerId, int $websiteId): LinkInterface
    {
        /** @var LinkInterface $link */
        $link = $this->linkFactory->create();
        $link->setCustomerId($customerId)
            ->setWebsiteId($websiteId)
            ->setProvider($profile->provider)
            ->setProviderUserId($profile->providerUserId)
            ->setProviderEmail($profile->email)
            ->setDisplayName($profile->displayName !== '' ? mb_substr($profile->displayName, 0, 255) : null)
            ->setLoginCount(0);
        return $this->linkRepository->save($link);
    }

    private function touchLink(Profile $profile, CustomerInterface $customer, int $websiteId): void
    {
        $link = $this->linkRepository->findByCustomerAndProvider((int)$customer->getId(), $profile->provider)
            ?? $this->createLink($profile, (int)$customer->getId(), $websiteId);
        $link->setLastLoginAt(gmdate('Y-m-d H:i:s'))
            ->setLoginCount($link->getLoginCount() + 1);
        if ($profile->hasEmail()) {
            $link->setProviderEmail($profile->email);
        }
        if ($profile->displayName !== '') {
            $link->setDisplayName(mb_substr($profile->displayName, 0, 255));
        }
        $this->linkRepository->save($link);
    }

    private function requirePending(): array
    {
        $pending = $this->pendingStorage->get();
        if ($pending === null) {
            throw new SocialLoginException(__('Your sign-in session has expired. Please try again.'));
        }
        return $pending;
    }

    private function getWebsiteId(int $storeId): int
    {
        return (int)$this->storeManager->getStore($storeId)->getWebsiteId();
    }

    private function getProviderName(string $code): string
    {
        $provider = $this->providerPool->get($code);
        return $provider !== null ? $provider->getName() : $code;
    }
}
