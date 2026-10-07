<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Config\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;
use Softaware\SocialLogin\Model\Provider\ProviderPool;
use Softaware\SocialLogin\Model\ResourceModel\Link as LinkResource;

/**
 * Providers for the admin grid (column labels and filter): the registered providers plus provider codes that
 * are stored in links but not installed (any more), shown with their code so such links stay visible.
 */
class Provider implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(
        private readonly ProviderPool $providerPool,
        private readonly ResourceConnection $resource
    ) {
    }

    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $options = [];
        $registered = $this->providerPool->getAll();
        foreach ($registered as $code => $provider) {
            $options[] = ['value' => $code, 'label' => $provider->getName()];
        }
        try {
            $connection = $this->resource->getConnection();
            $stored = $connection->fetchCol(
                $connection->select()->distinct()
                    ->from($this->resource->getTableName(LinkResource::TABLE), ['provider'])
                    ->order('provider ASC')
            );
        } catch (\Exception $e) {
            $stored = [];
        }
        foreach ($stored as $code) {
            $code = (string)$code;
            if ($code !== '' && !isset($registered[$code])) {
                $options[] = ['value' => $code, 'label' => $code];
            }
        }
        return $this->options = $options;
    }
}
