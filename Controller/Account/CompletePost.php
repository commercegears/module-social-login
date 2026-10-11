<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Account;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Psr\Log\LoggerInterface;
use CommerceGears\SocialLogin\Exception\LoginRefusedException;
use CommerceGears\SocialLogin\Model\Account\SocialAccountService;
use CommerceGears\SocialLogin\Model\FlowResponder;

/**
 * Handles the "Complete your account" form (form key validated by Magento).
 */
class CompletePost implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly SocialAccountService $accountService,
        private readonly FlowResponder $responder,
        private readonly MessageManager $messageManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute()
    {
        $pending = $this->accountService->getPending();
        try {
            $result = $this->accountService->completeDetails(
                (string)$this->request->getParam('email'),
                (string)$this->request->getParam('firstname'),
                (string)$this->request->getParam('lastname')
            );
            return $this->responder->forResult($result, $pending ? $pending['profile']->provider : '', false);
        } catch (LoginRefusedException $e) {
            if (!$e->isExplained()) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $this->redirectFactory->create()->setPath('customer/account/login');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Social login: complete account: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Sign-in failed. Please try again.'));
        }
        return $this->redirectFactory->create()->setPath(
            $this->accountService->getPending() ? 'sociallogin/account/complete' : 'customer/account/login'
        );
    }
}
