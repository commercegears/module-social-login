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
