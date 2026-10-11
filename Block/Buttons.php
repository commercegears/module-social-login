<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;
use CommerceGears\SocialLogin\Model\ThemeDetector;
use CommerceGears\SocialLogin\ViewModel\ProviderIcons;

/**
 * Social sign-in buttons. Used on the login and registration pages, in the Hyvä authentication
 * popup, as a CMS widget ("CommerceGears Social Login Buttons") and as a layout block:
 *
 *   <block class="CommerceGears\SocialLogin\Block\Buttons" name="my.social.buttons">
 *       <arguments><argument name="place" xsi:type="string">widget</argument></arguments>
 *   </block>
 *
 * Picks the Luma or Hyvä template automatically. Renders nothing customer-specific, so it is
 * safe on full-page-cached pages (the widget hides itself for signed-in customers in the browser).
 */
class Buttons extends Template implements BlockInterface
{
    public const TEMPLATE_LUMA = 'CommerceGears_SocialLogin::buttons.phtml';
    public const TEMPLATE_HYVA = 'CommerceGears_SocialLogin::hyva/buttons.phtml';

    protected $_template = self::TEMPLATE_LUMA;

    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly ProviderPool $providerPool,
        private readonly ProviderIcons $icons,
        private readonly ThemeDetector $themeDetector,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getTemplate()
    {
        $template = parent::getTemplate();
        if (($template === null || $template === '' || $template === self::TEMPLATE_LUMA) && $this->themeDetector->isHyva()) {
            return self::TEMPLATE_HYVA;
        }
        return $template;
    }

    public function getPlace(): string
    {
        return (string)($this->getData('place') ?: 'widget');
    }

    public function shouldRender(): bool
    {
        $place = $this->getPlace();
        $visible = $place === 'widget' ? $this->config->isEnabled() : $this->config->isShownOn($place);
        return $visible && $this->getButtons() !== [];
    }

    /**
     * @return array<int, array{code: string, label: string, url: string, icon: string}>
     */
    public function getButtons(): array
    {
        if (!$this->hasData('_buttons')) {
            $buttons = [];
            foreach ($this->providerPool->getAvailable() as $code => $provider) {
                $buttons[] = [
                    'code' => $code,
                    'name' => $provider->getName(),
                    'label' => $this->providerPool->getLabel($provider),
                    'url' => $this->getUrl('sociallogin/auth/start', ['provider' => $code, '_nosid' => true]),
                    'icon' => $this->icons->getSvg($code),
                ];
            }
            $this->setData('_buttons', $buttons);
        }
        return $this->getData('_buttons');
    }

    public function getButtonStyle(): string
    {
        $style = (string)$this->getData('button_style');
        return in_array($style, [Config::STYLE_ICON, Config::STYLE_ICON_TEXT], true)
            ? $style
            : $this->config->getButtonStyle();
    }

    public function getButtonTheme(): string
    {
        $theme = (string)$this->getData('button_theme');
        return in_array($theme, [Config::THEME_LIGHT, Config::THEME_DARK], true)
            ? $theme
            : $this->config->getButtonTheme();
    }

    public function isPopupMode(): bool
    {
        return $this->config->getLoginMode() === Config::LOGIN_MODE_POPUP;
    }

    public function getHeading(): string
    {
        if ($this->hasData('title')) {
            return (string)$this->getData('title');
        }
        return match ($this->getPlace()) {
            'register_page' => (string)__('Create an account with'),
            default => (string)__('Sign in with'),
        };
    }

    public function showDivider(): bool
    {
        return in_array($this->getPlace(), ['login_page', 'register_page', 'auth_popup'], true);
    }

    /**
     * Widgets on cached pages hide themselves for signed-in customers (in the browser).
     */
    public function isGuestOnly(): bool
    {
        return $this->getPlace() === 'widget';
    }

    public function getScriptUrl(): string
    {
        return $this->getViewFileUrl('CommerceGears_SocialLogin::js/social-login.js');
    }

    protected function _toHtml()
    {
        return $this->shouldRender() ? parent::_toHtml() : '';
    }
}
