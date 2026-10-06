<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Plugin;

use Magento\Customer\Block\Account\AuthenticationPopup;
use Magento\Framework\Serialize\Serializer\Json;
use Softaware\SocialLogin\Model\ButtonsJsConfig;

/**
 * Adds the buttons to Luma's authentication popup ("Checkout using your account").
 */
class AuthenticationPopupPlugin
{
    public function __construct(
        private readonly ButtonsJsConfig $buttonsJsConfig,
        private readonly Json $json
    ) {
    }

    public function afterGetJsLayout(AuthenticationPopup $subject, string $result): string
    {
        $component = $this->buttonsJsConfig->get('auth_popup', 'auth_popup', 'before', (string)__('Sign in with'));
        if ($component === null) {
            return $result;
        }
        try {
            $layout = $this->json->unserialize($result);
        } catch (\InvalidArgumentException $e) {
            return $result;
        }
        if (!isset($layout['components']['authenticationPopup'])) {
            return $result;
        }
        $layout['components']['authenticationPopup']['children']['softaware-social-login'] = $component;
        return (string)$this->json->serialize($layout);
    }
}
