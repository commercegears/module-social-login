<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Controller\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\View\Result\PageFactory;
use Softaware\SocialLogin\Model\Account\PendingStorage;

/**
 * "Complete your account": asks for the email (and/or name) the provider did not return.
 */
class Complete implements HttpGetActionInterface
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
        if ($this->pendingStorage->get() === null) {
            $this->messageManager->addErrorMessage(__('Your sign-in session has expired. Please try again.'));
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Complete Your Account'));
        return $page;
    }
}
