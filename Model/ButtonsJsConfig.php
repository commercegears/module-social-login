<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model;

use Magento\Framework\UrlInterface;
use Softaware\SocialLogin\Model\Provider\ProviderPool;
use Softaware\SocialLogin\ViewModel\ProviderIcons;

/**
 * jsLayout configuration of the Knockout buttons component used inside Luma's authentication
 * popup and the checkout (where the markup is rendered by Knockout).
 */
class ButtonsJsConfig
{
    public function __construct(
        private readonly Config $config,
        private readonly ProviderPool $providerPool,
        private readonly ProviderIcons $icons,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @param string $place Config flag under display/ (auth_popup, checkout)
     * @param string $cssPlace Modifier class for styling (auth_popup, checkout_auth, checkout_email)
     */
    public function get(string $place, string $cssPlace, string $displayArea, string $heading): ?array
    {
        if (!$this->config->isShownOn($place)) {
            return null;
        }
        $buttons = [];
        foreach ($this->providerPool->getAvailable() as $code => $provider) {
            $buttons[] = [
                'code' => $code,
                'label' => $this->providerPool->getLabel($provider),
                'url' => $this->urlBuilder->getUrl('sociallogin/auth/start', ['provider' => $code, '_nosid' => true]),
                'icon' => $this->icons->getSvg($code),
            ];
        }
        if ($buttons === []) {
            return null;
        }
        return [
            'component' => 'Softaware_SocialLogin/js/view/social-buttons',
            'displayArea' => $displayArea,
            'sortOrder' => 5,
            'config' => [
                'buttons' => $buttons,
                'heading' => $heading,
                'place' => $cssPlace,
                'buttonStyle' => $this->config->getButtonStyle(),
                'buttonTheme' => $this->config->getButtonTheme(),
                'popup' => $this->config->getLoginMode() === Config::LOGIN_MODE_POPUP,
                'showDivider' => true,
            ],
        ];
    }
}
