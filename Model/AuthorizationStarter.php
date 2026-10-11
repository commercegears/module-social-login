<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\SocialLogin\Model\Provider\ProviderInterface;

/**
 * Creates state, nonce and PKCE verifier, stores them and returns the provider's consent URL.
 */
class AuthorizationStarter
{
    public function __construct(
        private readonly StateStore $stateStore,
        private readonly BrowserBinding $browserBinding,
        private readonly CallbackUrl $callbackUrl,
        private readonly CustomerSession $customerSession,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function start(
        ProviderInterface $provider,
        string $intent,
        string $returnUrl,
        bool $popup,
        int $customerId = 0
    ): string {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $request = new AuthRequest(
            $provider->getCode(),
            StateStore::randomToken(32),
            StateStore::randomToken(32),
            StateStore::randomToken(48),
            $this->callbackUrl->get($provider->getCode(), $storeId),
            $storeId,
            $intent,
            $returnUrl,
            $popup,
            $customerId,
            (string)$this->customerSession->getSessionId()
        );
        $this->stateStore->save($request, $this->browserBinding->getOrCreate());
        return $provider->getAuthorizationUrl($request);
    }
}
