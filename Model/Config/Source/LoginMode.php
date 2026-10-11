<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\SocialLogin\Model\Config;

class LoginMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::LOGIN_MODE_REDIRECT, 'label' => __('Full-page redirect')],
            ['value' => Config::LOGIN_MODE_POPUP, 'label' => __('Popup window')],
        ];
    }
}
