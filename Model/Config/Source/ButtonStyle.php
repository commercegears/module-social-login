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
