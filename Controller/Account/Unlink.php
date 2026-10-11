<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Account;

use Magento\Customer\Controller\AbstractAccount;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\Account\SocialAccountService;
use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;

/**
 * Disconnects a provider (POST with form key). Refused when it is the only way to sign in.
 */
class Unlink extends AbstractAccount implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private readonly CustomerSession $customerSession,
        private readonly CustomerRegistry $customerRegistry,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly SocialAccountService $accountService,
        private readonly ProviderPool $providerPool,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sociallogin/account');
        if (!$this->config->isEnabled()) {
            return $redirect;
        }
        $customerId = (int)$this->customerSession->getCustomerId();
        $code = (string)$this->getRequest()->getParam('provider');
        $link = $this->linkRepository->findByCustomerAndProvider($customerId, $code);
        if ($link === null) {
            $this->messageManager->addErrorMessage(__('This account is not connected.'));
            return $redirect;
        }
        $hasPassword = (string)$this->customerRegistry->retrieveSecureData($customerId)->getPasswordHash() !== '';
        if (!$this->accountService->canUnlink($customerId, $code, $hasPassword)) {
            $this->messageManager->addErrorMessage(__(
                'You cannot disconnect your only sign-in method. Set a password first '
                . '(use "Forgot Your Password?" on the sign-in page) or connect another account.'
            ));
            return $redirect;
        }
        $this->linkRepository->delete($link);
        $provider = $this->providerPool->get($code);
        $this->messageManager->addSuccessMessage(
            __('Your %1 account has been disconnected.', $provider ? $provider->getName() : $code)
        );
        return $redirect;
    }
}
