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
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Psr\Log\LoggerInterface;
use CommerceGears\SocialLogin\Exception\LoginRefusedException;
use CommerceGears\SocialLogin\Model\Account\SocialAccountService;
use CommerceGears\SocialLogin\Model\FlowResponder;

/**
 * Checks the store password of the existing account, then links and signs in (form key validated by Magento).
 */
class ConfirmPost implements HttpPostActionInterface
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
        $password = (string)$this->request->getParam('password');
        if ($pending === null) {
            $this->messageManager->addErrorMessage(__('Your sign-in session has expired. Please try again.'));
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }
        if ($password === '') {
            $this->messageManager->addErrorMessage(__('Please enter your password.'));
            return $this->redirectFactory->create()->setPath('sociallogin/account/confirm');
        }
        try {
            $result = $this->accountService->completeWithPassword($password);
            return $this->responder->forResult($result, $pending['profile']->provider, false);
        } catch (EmailNotConfirmedException $e) {
            $this->messageManager->addErrorMessage(
                __('This account is not confirmed yet. Please use the confirmation link we sent to your email address.')
            );
        } catch (UserLockedException $e) {
            $this->messageManager->addErrorMessage(
                __('The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later.')
            );
        } catch (AuthenticationException $e) {
            $this->messageManager->addErrorMessage(__('The password is not correct. Please try again.'));
        } catch (LoginRefusedException $e) {
            if (!$e->isExplained()) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $this->redirectFactory->create()->setPath('customer/account/login');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Social login: confirm password: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Sign-in failed. Please try again.'));
        }
        return $this->redirectFactory->create()->setPath(
            $this->accountService->getPending() ? 'sociallogin/account/confirm' : 'customer/account/login'
        );
    }
}
