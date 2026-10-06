<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to Stores > Configuration > Softaware > Social Login.
 */
class Config
{
    public const SECTION = 'softaware_sociallogin';

    public const LOGIN_MODE_REDIRECT = 'redirect';
    public const LOGIN_MODE_POPUP = 'popup';

    public const STYLE_ICON_TEXT = 'icon_text';
    public const STYLE_ICON = 'icon';

    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';

    public const LINK_AUTO = 'auto';
    public const LINK_PASSWORD = 'password';

    public const REDIRECT_STAY = 'stay';
    public const REDIRECT_ACCOUNT = 'account';
    public const REDIRECT_CUSTOM = 'custom';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->flag('general/enabled', $storeId);
    }

    public function getLoginMode(?int $storeId = null): string
    {
        return $this->value('general/login_mode', $storeId) === self::LOGIN_MODE_POPUP
            ? self::LOGIN_MODE_POPUP
            : self::LOGIN_MODE_REDIRECT;
    }

    public function getButtonStyle(?int $storeId = null): string
    {
        return $this->value('general/button_style', $storeId) === self::STYLE_ICON
            ? self::STYLE_ICON
            : self::STYLE_ICON_TEXT;
    }

    public function getButtonTheme(?int $storeId = null): string
    {
        return $this->value('general/button_theme', $storeId) === self::THEME_DARK
            ? self::THEME_DARK
            : self::THEME_LIGHT;
    }

    public function isShownOn(string $place, ?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->flag('display/' . $place, $storeId);
    }

    public function getLinkMode(?int $storeId = null): string
    {
        return $this->value('account/link_mode', $storeId) === self::LINK_PASSWORD
            ? self::LINK_PASSWORD
            : self::LINK_AUTO;
    }

    public function isWelcomeEmailEnabled(?int $storeId = null): bool
    {
        return $this->flag('account/send_welcome_email', $storeId);
    }

    /**
     * Customer group for new social sign-ups; 0 means "use the store's default group".
     */
    public function getCustomerGroupId(?int $storeId = null): int
    {
        return (int)$this->value('account/customer_group', $storeId);
    }

    public function getRedirectTarget(?int $storeId = null): string
    {
        $value = (string)$this->value('account/redirect', $storeId);
        return in_array($value, [self::REDIRECT_STAY, self::REDIRECT_ACCOUNT, self::REDIRECT_CUSTOM], true)
            ? $value
            : self::REDIRECT_STAY;
    }

    public function getCustomRedirectUrl(?int $storeId = null): string
    {
        return trim((string)$this->value('account/redirect_custom_url', $storeId));
    }

    public function isProviderEnabled(string $provider, ?int $storeId = null): bool
    {
        return $this->flag($provider . '/enabled', $storeId);
    }

    public function getProviderValue(string $provider, string $field, ?int $storeId = null): string
    {
        return trim((string)$this->value($provider . '/' . $field, $storeId));
    }

    /**
     * Plain value of a secret field. The fields are declared with the Encrypted backend model in
     * etc/config.xml, so Magento decrypts them when the configuration is loaded.
     */
    public function getProviderSecret(string $provider, string $field, ?int $storeId = null): string
    {
        return trim((string)$this->value($provider . '/' . $field, $storeId));
    }

    private function value(string $path, ?int $storeId): mixed
    {
        return $this->scopeConfig->getValue(self::SECTION . '/' . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function flag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(self::SECTION . '/' . $path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
