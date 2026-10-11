<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Profile;

/**
 * Decides how a provider identity maps to a customer account. Has no side effects.
 */
class AccountMatcher
{
    public function __construct(
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    public function decide(Profile $profile, int $websiteId, string $linkMode): Decision
    {
        $link = $this->linkRepository->findByProviderUser($profile->provider, $profile->providerUserId, $websiteId);
        if ($link !== null) {
            return new Decision(Decision::LOGIN_LINKED, $link->getCustomerId());
        }
        if (!$profile->hasEmail()) {
            return new Decision(Decision::ASK_DETAILS);
        }
        $customerId = $this->findCustomerId((string)$profile->email, $websiteId);
        if ($customerId !== null) {
            // Only an address the provider is authoritative for may sign in to an existing account.
            return $profile->emailTrusted && $linkMode === Config::LINK_AUTO
                ? new Decision(Decision::LINK_EXISTING, $customerId)
                : new Decision(Decision::ASK_PASSWORD, $customerId);
        }
        if (trim($profile->firstName) === '' || trim($profile->lastName) === '') {
            return new Decision(Decision::ASK_DETAILS);
        }
        return new Decision(Decision::CREATE);
    }

    public function findCustomerId(string $email, int $websiteId): ?int
    {
        try {
            return (int)$this->customerRepository->get($email, $websiteId)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
