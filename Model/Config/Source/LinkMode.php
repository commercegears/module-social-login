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

class LinkMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::LINK_AUTO, 'label' => __('Link automatically when the provider has verified the email')],
            ['value' => Config::LINK_PASSWORD, 'label' => __('Always ask for the account password')],
        ];
    }
}
