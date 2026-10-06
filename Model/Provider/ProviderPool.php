<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Provider;

use Softaware\SocialLogin\Model\Config;

/**
 * All registered providers (see di.xml), with lookups for the enabled ones in a store.
 */
class ProviderPool
{
    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(
        private readonly Config $config,
        private readonly array $providers = []
    ) {
        foreach ($providers as $provider) {
            if (!$provider instanceof ProviderInterface) {
                throw new \InvalidArgumentException('Social login providers must implement ProviderInterface.');
            }
        }
    }

    /**
     * @return array<string, ProviderInterface>
     */
    public function getAll(): array
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result[$provider->getCode()] = $provider;
        }
        return $result;
    }

    public function get(string $code): ?ProviderInterface
    {
        return $this->getAll()[$code] ?? null;
    }

    /**
     * Enabled and configured providers for the store, in the configured sort order.
     *
     * @return array<string, ProviderInterface>
     */
    public function getAvailable(?int $storeId = null): array
    {
        if (!$this->config->isEnabled($storeId)) {
            return [];
        }
        $available = array_filter(
            $this->getAll(),
            static fn (ProviderInterface $provider) => $provider->isAvailable($storeId)
        );
        uksort($available, function (string $a, string $b) use ($storeId): int {
            return [(int)$this->config->getProviderValue($a, 'sort_order', $storeId), $a]
                <=> [(int)$this->config->getProviderValue($b, 'sort_order', $storeId), $b];
        });
        return $available;
    }

    public function getLabel(ProviderInterface $provider, ?int $storeId = null): string
    {
        $label = $this->config->getProviderValue($provider->getCode(), 'label', $storeId);
        return $label !== '' ? $label : (string)__('Continue with %1', $provider->getName());
    }
}
