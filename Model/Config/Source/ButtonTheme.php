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
