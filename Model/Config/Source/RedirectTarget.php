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
