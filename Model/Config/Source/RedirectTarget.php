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

class RedirectTarget implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::REDIRECT_STAY, 'label' => __('Stay on the current page')],
            ['value' => Config::REDIRECT_ACCOUNT, 'label' => __('My Account dashboard')],
            ['value' => Config::REDIRECT_CUSTOM, 'label' => __('Custom URL')],
        ];
    }
}
