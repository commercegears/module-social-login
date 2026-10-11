<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\View\Result\PageFactory;
use CommerceGears\SocialLogin\Model\Account\PendingStorage;

/**
 * "Connect your account": an account with this email exists; its password proves ownership.
 */
class Confirm implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly PendingStorage $pendingStorage,
        private readonly CustomerSession $customerSession,
        private readonly MessageManager $messageManager
    ) {
    }

    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account');
        }
        $pending = $this->pendingStorage->get();
        if ($pending === null || $pending['customer_id'] <= 0) {
            $this->messageManager->addErrorMessage(__('Your sign-in session has expired. Please try again.'));
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Connect Your Account'));
        return $page;
    }
}
