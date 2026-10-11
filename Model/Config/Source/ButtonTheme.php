<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use CommerceGears\SocialLogin\Model\Config;

class ButtonTheme implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::THEME_LIGHT, 'label' => __('Light')],
            ['value' => Config::THEME_DARK, 'label' => __('Dark')],
        ];
    }
}
