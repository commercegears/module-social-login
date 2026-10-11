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

class ButtonStyle implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::STYLE_ICON_TEXT, 'label' => __('Icon and text')],
            ['value' => Config::STYLE_ICON, 'label' => __('Icon only')],
        ];
    }
}
