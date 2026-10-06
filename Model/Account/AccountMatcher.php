<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Softaware\SocialLogin\Api\LinkRepositoryInterface;
use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\Profile;

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
            return $profile->emailVerified && $linkMode === Config::LINK_AUTO
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
