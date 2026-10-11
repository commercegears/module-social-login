<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use CommerceGears\SocialLogin\Model\ButtonsJsConfig;

/**
 * Adds the buttons to the checkout "Sign In" dropdown and, optionally, above the email field.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    public function __construct(
        private readonly ButtonsJsConfig $buttonsJsConfig
    ) {
    }

    public function process($jsLayout)
    {
        $auth = $this->buttonsJsConfig->get('auth_popup', 'checkout_auth', 'before', (string)__('Sign in with'));
        if ($auth !== null && isset($jsLayout['components']['checkout']['children']['authentication'])) {
            $jsLayout['components']['checkout']['children']['authentication']['children']['commercegears-social-login'] = $auth;
        }

        $email = $this->buttonsJsConfig->get('checkout', 'checkout_email', 'before-login-form', (string)__('Sign in faster with'));
        if ($email !== null) {
            $steps = $jsLayout['components']['checkout']['children']['steps']['children'] ?? [];
            if (isset($steps['shipping-step']['children']['shippingAddress']['children']['customer-email']
                ['children']['before-login-form'])) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['customer-email']['children']['before-login-form']['children']
                    ['commercegears-social-login'] = $email;
            }
            if (isset($steps['billing-step']['children']['payment']['children']['customer-email']
                ['children']['before-login-form'])) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['customer-email']['children']['before-login-form']['children']
                    ['commercegears-social-login'] = $email;
            }
        }
        return $jsLayout;
    }
}
