<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\SocialLogin\Model\Config;

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
