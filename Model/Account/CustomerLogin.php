<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Account;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Softaware\SocialLogin\Exception\SocialLoginException;

/**
 * Signs the customer in the same way Magento's login form does.
 */
class CustomerLogin
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory
    ) {
    }

    /**
     * @throws SocialLoginException when another module refused the sign-in (e.g. account approval pending)
     */
    public function login(CustomerInterface $customer): void
    {
        $this->customerSession->setCustomerDataAsLoggedIn($customer);
        if (!$this->customerSession->isLoggedIn()) {
            throw SocialLoginException::withDetail(
                'We could not sign you in to this account.',
                sprintf('customer %d not signed in after setCustomerDataAsLoggedIn (refused by another module)', (int) $customer->getId())
            );
        }
        // Makes Luma customer-data and Hyvä private content reload the sections.
        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata()->setPath('/');
            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }
    }
}
