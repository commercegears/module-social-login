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
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\Profile;

/**
 * Creates the store account for a new social sign-up (no password; the customer can set one later
 * with "Forgot your password?").
 */
class CustomerCreator
{
    public function __construct(
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AccountManagementInterface $accountManagement,
        private readonly AccountConfirmation $accountConfirmation,
        private readonly GroupManagementInterface $groupManagement,
        private readonly EmailNotificationInterface $emailNotification,
        private readonly StoreManagerInterface $storeManager,
        private readonly EventManager $eventManager,
        private readonly Config $config,
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

        if (!$profile->emailVerified
            && $this->accountConfirmation->isConfirmationRequired($websiteId, null, $email)
        ) {
            // Unverified address and the store requires confirmation: same as a normal registration.
            $saved = $this->accountManagement->createAccount($customer);
            $needsConfirmation = true;
        } else {
            $saved = $this->customerRepository->save($customer);
            $needsConfirmation = false;
            if ($this->config->isWelcomeEmailEnabled($storeId)) {
                try {
                    $this->emailNotification->newAccount(
                        $saved,
                        EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD,
                        '',
                        $storeId
                    );
                } catch (\Exception $e) {
                    $this->logger->warning('Social login: welcome email not sent: ' . $e->getMessage());
                }
            }
        }

        $this->eventManager->dispatch('softaware_sociallogin_customer_created', [
            'customer' => $saved,
            'provider' => $profile->provider,
            'needs_confirmation' => $needsConfirmation,
        ]);

        return ['customer' => $saved, 'needs_confirmation' => $needsConfirmation];
    }
}
