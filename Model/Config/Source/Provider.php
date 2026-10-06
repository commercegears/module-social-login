<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Softaware\SocialLogin\Model\Provider\ProviderPool;

/**
 * Registered providers (admin grid filter).
 */
class Provider implements OptionSourceInterface
{
    public function __construct(
        private readonly ProviderPool $providerPool
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->providerPool->getAll() as $code => $provider) {
            $options[] = ['value' => $code, 'label' => $provider->getName()];
        }
        return $options;
    }
}
