<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Controller\Auth;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Softaware\SocialLogin\Exception\LoginRefusedException;
use Softaware\SocialLogin\Exception\SocialLoginException;
use Softaware\SocialLogin\Model\Account\SocialAccountService;
use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\BrowserBinding;
use Softaware\SocialLogin\Model\CallbackUrl;
use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\FlowResponder;
use Softaware\SocialLogin\Model\Provider\ProviderPool;
use Softaware\SocialLogin\Model\SessionRestorer;
use Softaware\SocialLogin\Model\StateStore;

/**
 * Redirect URI registered with the providers.
 *
 * CSRF protection is the OAuth "state": random, single-use, stored server-side and bound to the
 * browser cookie that started the sign-in. Magento's form key cannot be used here because the
 * request comes from the provider (and Apple posts it).
 */
class Callback implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    private const POST_PARAMS = ['code', 'state', 'error', 'error_description', 'user'];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResultFactory $resultFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly ProviderPool $providerPool,
        private readonly StateStore $stateStore,
        private readonly BrowserBinding $browserBinding,
        private readonly SessionRestorer $sessionRestorer,
        private readonly CallbackUrl $callbackUrl,
        private readonly SocialAccountService $accountService,
        private readonly FlowResponder $responder,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $code = (string)$this->request->getParam('provider');
        $provider = $this->providerPool->get($code);
        if ($provider === null) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $state = (string)$this->request->getParam('state');
        $binding = $this->browserBinding->get();

        /** @var HttpRequest $httpRequest */
        $httpRequest = $this->request;
        if ($httpRequest->isPost()) {
            return $this->handleFormPost($provider->getCode(), $state, $binding);
        }

        $authRequest = $this->stateStore->consume($state, $binding);
        $popup = $authRequest !== null && $authRequest->popup;
        $isLink = $authRequest !== null && $authRequest->intent === AuthRequest::INTENT_LINK;
        try {
            if ($authRequest === null) {
                throw SocialLoginException::withDetail(
                    'Your sign-in request has expired or was started in another browser. Please try again.',
                    sprintf('%s: unknown, expired or unbound state', $code)
                );
            }
            if ($authRequest->provider !== $provider->getCode()
                || $authRequest->storeId !== (int)$this->storeManager->getStore()->getId()
            ) {
                throw SocialLoginException::withDetail(
                    'Your sign-in request is not valid. Please try again.',
                    sprintf('%s: state issued for %s/store %d', $code, $authRequest->provider, $authRequest->storeId)
                );
            }
            if (!$provider->isAvailable($authRequest->storeId)) {
                throw new SocialLoginException(__('This sign-in option is not available.'));
            }
            if ($provider->usesFormPost()) {
                $params = $authRequest->callbackParams;
                if ($params === []) {
                    throw SocialLoginException::withDetail(
                        'Your sign-in request is not valid. Please try again.',
                        sprintf('%s: expected a form_post callback', $code)
                    );
                }
            } else {
                $params = array_intersect_key($this->request->getParams(), array_flip(self::POST_PARAMS));
            }
            if (!empty($params['error'])) {
                throw SocialLoginException::withDetail(
                    in_array($params['error'], ['access_denied', 'user_cancelled_login', 'user_cancelled_authorize'], true)
                        ? 'Sign-in was cancelled.'
                        : 'The sign-in provider returned an error. Please try again.',
                    sprintf('%s: provider error "%s"', $code, mb_substr((string)$params['error'], 0, 100))
                );
            }

            $profile = $provider->fetchProfile($authRequest, $params);
            $result = $this->accountService->handle($profile, $authRequest);
            return $this->responder->forResult($result, $provider->getCode(), $popup);
        } catch (LoginRefusedException $e) {
            $this->logger->info(sprintf('Social login [%s]: %s', $code, $e->getLogDetail()));
            return $this->responder->forError($e->isExplained() ? '' : $e->getMessage(), $popup, $isLink);
        } catch (SocialLoginException $e) {
            $this->logger->warning(sprintf('Social login [%s]: %s', $code, $e->getLogDetail()));
            return $this->responder->forError($e->getMessage(), $popup, $isLink);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->warning('Social login: ' . $code . ': ' . $e->getMessage());
            return $this->responder->forError($e->getMessage(), $popup, $isLink);
        } catch (\Throwable $e) {
            $this->logger->error('Social login: ' . $code . ': ' . $e->getMessage(), ['exception' => $e]);
            return $this->responder->forError((string)__('Sign-in failed. Please try again.'), $popup, $isLink);
        }
    }

    /**
     * Apple's form_post: store the posted values with the state, restore the browser's session
     * cookie and continue with a same-site GET (which carries the session cookie).
     */
    private function handleFormPost(string $code, string $state, string $binding): ResultInterface
    {
        $authRequest = $this->stateStore->peek($state, $binding);
        if ($authRequest === null || $authRequest->provider !== $code || $authRequest->callbackParams !== []) {
            $this->logger->warning(sprintf('Social login: %s: form_post with unknown, expired or unbound state', $code));
            return $this->responder->forError(
                (string)__('Your sign-in request has expired or was started in another browser. Please try again.'),
                false,
                false
            );
        }
        $params = [];
        foreach (self::POST_PARAMS as $name) {
            $value = $this->request->getPost($name);
            if (is_string($value) && $value !== '' && strlen($value) <= 4096) {
                $params[$name] = $value;
            }
        }
        $this->stateStore->save($authRequest->withCallbackParams($params), $binding);
        $this->sessionRestorer->restore($authRequest->sessionId);

        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setHttpResponseCode(303);
        return $redirect->setUrl(
            $this->callbackUrl->get($code, $authRequest->storeId) . '?' . http_build_query(['state' => $state])
        );
    }
}
