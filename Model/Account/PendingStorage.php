<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Account;

use Magento\Customer\Model\Session as CustomerSession;
use CommerceGears\SocialLogin\Model\Profile;

/**
 * Keeps a verified provider identity in the session while the customer completes a step
 * (enter email/name, or confirm the password of an existing account). Expires after 15 minutes.
 */
class PendingStorage
{
    private const KEY = 'commercegears_sociallogin_pending';
    private const TTL = 900;

    public function __construct(
        private readonly CustomerSession $customerSession
    ) {
    }

    public function save(Profile $profile, int $storeId, string $returnUrl, int $customerId = 0): void
    {
        $this->customerSession->setData(self::KEY, [
            'profile' => $profile->toArray(),
            'store_id' => $storeId,
            'return_url' => $returnUrl,
            'customer_id' => $customerId,
            'created_at' => time(),
        ]);
    }

    /**
     * @return array{profile: Profile, store_id: int, return_url: string, customer_id: int}|null
     */
    public function get(): ?array
    {
        $data = $this->customerSession->getData(self::KEY);
        if (!is_array($data) || (int)($data['created_at'] ?? 0) < time() - self::TTL) {
            return null;
        }
        return [
            'profile' => Profile::fromArray((array)$data['profile']),
            'store_id' => (int)$data['store_id'],
            'return_url' => (string)$data['return_url'],
            'customer_id' => (int)$data['customer_id'],
        ];
    }

    public function clear(): void
    {
        $this->customerSession->unsetData(self::KEY);
    }
}
