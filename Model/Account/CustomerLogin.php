<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Account;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use CommerceGears\SocialLogin\Exception\LoginRefusedException;

/**
 * Signs the customer in the same way Magento's login form does.
 */
class CustomerLogin
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly MessageManager $messageManager
    ) {
    }

    /**
     * Session fixation: Magento regenerates the session id in setCustomerDataAsLoggedIn().
     *
     * @throws LoginRefusedException when another module refused the sign-in (e.g. account approval pending)
     */
    public function login(CustomerInterface $customer): void
    {
        $messagesBefore = $this->messageManager->getMessages(false)->getCount();
        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        if (!$this->customerSession->isLoggedIn()
            || (int)$this->customerSession->getCustomerId() !== (int)$customer->getId()
        ) {
            throw LoginRefusedException::forCustomer(
                (int)$customer->getId(),
                $this->messageManager->getMessages(false)->getCount() > $messagesBefore
            );
        }
        // Makes Luma customer-data and Hyvä private content reload the sections.
        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata()->setPath('/');
            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }
    }
}
