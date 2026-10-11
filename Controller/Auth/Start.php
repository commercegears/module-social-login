<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Auth;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use CommerceGears\SocialLogin\Exception\SocialLoginException;
use CommerceGears\SocialLogin\Model\AuthorizationStarter;
use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;
use CommerceGears\SocialLogin\Model\RedirectResolver;

/**
 * GET: start a sign-in. POST (form key checked by Magento): connect a provider to the signed-in account.
 */
class Start implements HttpGetActionInterface, HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResultFactory $resultFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly ProviderPool $providerPool,
        private readonly AuthorizationStarter $starter,
        private readonly Config $config,
        private readonly CustomerSession $customerSession,
        private readonly RedirectResolver $redirectResolver,
        private readonly MessageManager $messageManager,
        private readonly UrlInterface $urlBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $provider = $this->providerPool->get((string)$this->request->getParam('provider'));
        if ($provider === null || !$provider->isAvailable()) {
            $this->messageManager->addErrorMessage(__('This sign-in option is not available.'));
            return $redirect->setUrl($this->redirectResolver->getLoginUrl());
        }

        $isLink = $this->request->isPost() && $this->request->getParam('intent') === AuthRequest::INTENT_LINK;
        if ($isLink && !$this->customerSession->isLoggedIn()) {
            return $redirect->setUrl($this->redirectResolver->getLoginUrl());
        }
        if (!$isLink && $this->customerSession->isLoggedIn()) {
            return $redirect->setUrl($this->urlBuilder->getUrl('customer/account'));
        }

        $returnUrl = $this->redirectResolver->sanitize((string)$this->request->getParam('return'));
        if ($returnUrl === '') {
            $returnUrl = $this->redirectResolver->sanitize((string)$this->request->getServer('HTTP_REFERER'));
        }
        $popup = !$isLink
            && $this->request->getParam('popup') === '1'
            && $this->config->getLoginMode() === Config::LOGIN_MODE_POPUP;

        try {
            $url = $this->starter->start(
                $provider,
                $isLink ? AuthRequest::INTENT_LINK : AuthRequest::INTENT_LOGIN,
                $returnUrl,
                $popup,
                $isLink ? (int)$this->customerSession->getCustomerId() : 0
            );
        } catch (SocialLoginException $e) {
            $this->logger->error('Social login: ' . $e->getLogDetail());
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setUrl($isLink
                ? $this->redirectResolver->getConnectedAccountsUrl()
                : $this->redirectResolver->getLoginUrl());
        }
        $redirect->setHeader('Cache-Control', 'no-store', true);
        return $redirect->setUrl($url);
    }
}
