<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\Config\ConfigInterface as SessionConfig;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Providers that post their callback (response_mode=form_post) do so cross-site, so the browser does not send the SameSite=Lax session
 * cookie and Magento starts a new, empty session. This puts the original session cookie back
 * (its id was stored server-side with the state, bound to the browser) before redirecting to
 * the same-site step that signs the customer in, so the cart and session survive.
 */
class SessionRestorer
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly SessionConfig $sessionConfig,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory
    ) {
    }

    public function restore(string $sessionId): void
    {
        if ($sessionId === '' || preg_match('/^[A-Za-z0-9,\-]{16,256}$/', $sessionId) !== 1
            || $sessionId === $this->customerSession->getSessionId()
        ) {
            return;
        }
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath((string)$this->sessionConfig->getCookiePath())
            ->setDomain((string)$this->sessionConfig->getCookieDomain())
            ->setSecure((bool)$this->sessionConfig->getCookieSecure())
            ->setHttpOnly((bool)$this->sessionConfig->getCookieHttpOnly())
            ->setSameSite((string)($this->sessionConfig->getCookieSameSite() ?: 'Lax'));
        $lifetime = (int)$this->sessionConfig->getCookieLifetime();
        if ($lifetime > 0) {
            $metadata->setDuration($lifetime);
        }
        $this->cookieManager->setPublicCookie($this->customerSession->getName(), $sessionId, $metadata);
    }
}
