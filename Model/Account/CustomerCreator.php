<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Profile;

/**
 * Creates the store account for a new social sign-up (no password; the customer can set one later
 * with "Forgot your password?").
 *
 * Email confirmation:
 * - Address verified by the provider: no confirmation, even when the store requires it (the provider has
 *   already proved it).
 * - Address NOT verified (typed in by the customer, or from a provider without a verification flag): the customer always has to confirm it with
 *   Magento's confirmation email, whether or not the store requires confirmation. Otherwise anybody could
 *   create an account in someone else's name and keep a back door into it through the linked provider
 *   ("pre-account hijacking").
 */
class CustomerCreator
{
    public function __construct(
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly GroupManagementInterface $groupManagement,
        private readonly EmailNotificationInterface $emailNotification,
        private readonly StoreManagerInterface $storeManager,
        private readonly EventManager $eventManager,
        private readonly Config $config,
        private readonly Random $random,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{customer: CustomerInterface, needs_confirmation: bool}
     */
    public function create(Profile $profile, int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = (int)$store->getWebsiteId();
        $email = (string)$profile->email;
        $groupId = $this->config->getCustomerGroupId($storeId)
            ?: (int)$this->groupManagement->getDefaultGroup($storeId)->getId();

        $customer = $this->customerFactory->create();
        $customer->setEmail($email)
            ->setFirstname(mb_substr(trim($profile->firstName), 0, 255))
            ->setLastname(mb_substr(trim($profile->lastName), 0, 255))
            ->setWebsiteId($websiteId)
            ->setStoreId($storeId)
            ->setGroupId($groupId);

        $needsConfirmation = !$profile->emailVerified;
        if ($needsConfirmation) {
            $customer->setConfirmation($this->random->getUniqueHash());
        }
        $saved = $this->customerRepository->save($customer);
        if (!$needsConfirmation && (string)$saved->getConfirmation() !== '') {
            // The store requires confirmation, but the provider has verified the address already.
            $saved->setConfirmation(null);
            $saved = $this->customerRepository->save($saved);
        }

        if ($needsConfirmation) {
            $this->sendEmail($saved, EmailNotificationInterface::NEW_ACCOUNT_EMAIL_CONFIRMATION, $storeId);
        } elseif ($this->config->isWelcomeEmailEnabled($storeId)) {
            $this->sendEmail($saved, EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD, $storeId);
        }

        $this->eventManager->dispatch('commercegears_sociallogin_customer_created', [
            'customer' => $saved,
            'provider' => $profile->provider,
            'needs_confirmation' => $needsConfirmation,
        ]);

        return ['customer' => $saved, 'needs_confirmation' => $needsConfirmation];
    }

    private function sendEmail(CustomerInterface $customer, string $type, int $storeId): void
    {
        try {
            $this->emailNotification->newAccount($customer, $type, '', $storeId);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Social login: %s email not sent: %s', $type, $e->getMessage()));
        }
    }
}
