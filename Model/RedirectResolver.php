<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Url\HostChecker;
use Magento\Framework\UrlInterface;

/**
 * Where to send the customer after signing in, and validation of "return to" URLs
 * (only URLs of this Magento installation are accepted: no open redirects).
 */
class RedirectResolver
{
    public function __construct(
        private readonly Config $config,
        private readonly UrlInterface $urlBuilder,
        private readonly HostChecker $hostChecker,
        private readonly DecoderInterface $urlDecoder
    ) {
    }

    public function getSuccessUrl(string $returnUrl, ?int $storeId = null): string
    {
        switch ($this->config->getRedirectTarget($storeId)) {
            case Config::REDIRECT_ACCOUNT:
                return $this->urlBuilder->getUrl('customer/account');
            case Config::REDIRECT_CUSTOM:
                $custom = $this->config->getCustomRedirectUrl($storeId);
                if ($custom !== '') {
                    $url = preg_match('#^https?://#i', $custom) === 1
                        ? $custom
                        : $this->urlBuilder->getUrl('', ['_direct' => ltrim($custom, '/')]);
                    if ($this->sanitize($url) !== '') {
                        return $url;
                    }
                }
                return $this->urlBuilder->getUrl('customer/account');
            default:
                return $this->resolveStay($returnUrl);
        }
    }

    public function getLoginUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account/login');
    }

    public function getConnectedAccountsUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/account');
    }

    /**
     * Returns the URL if it belongs to this store installation, otherwise ''.
     */
    public function sanitize(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || preg_match('#^https?://#i', $url) !== 1) {
            return '';
        }
        // Browsers read "\" as "/" and drop tabs/newlines, PHP's parse_url() does not: refuse anything where the
        // two could disagree about the host (e.g. "https://evil.example\@shop.example/", "https:///evil.example").
        if (preg_match('/[\\\\\x00-\x20\x7f]/', $url) === 1) {
            return '';
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }
        // The authority must be exactly scheme://host[:port], so the host PHP sees is the host the browser uses.
        if (preg_match('#^https?://([^/?\#]*)#i', $url, $authority) !== 1
            || strcasecmp($authority[1], $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '')) !== 0
        ) {
            return '';
        }
        if (!$this->hostChecker->isOwnOrigin($url)) {
            return '';
        }
        if (preg_match('#/sociallogin/(auth|test)/#', (string)parse_url($url, PHP_URL_PATH)) === 1) {
            return '';
        }
        return $url;
    }

    private function resolveStay(string $returnUrl): string
    {
        $url = $this->sanitize($returnUrl);
        $path = (string)parse_url($url, PHP_URL_PATH);
        if ($url !== '' && preg_match('#/customer/account/(login|create|createpost|loginpost|logoutsuccess|logout)#i', $path) === 1) {
            // Signed in from the login/registration page: honour its "referer", else the account dashboard.
            if (preg_match('#/referer/([A-Za-z0-9,_\-]+)#', $path, $matches) === 1) {
                $referer = $this->sanitize((string)$this->urlDecoder->decode($matches[1]));
                if ($referer !== '') {
                    return $referer;
                }
            }
            $url = '';
        }
        return $url !== '' ? $url : $this->urlBuilder->getUrl('customer/account');
    }
}
