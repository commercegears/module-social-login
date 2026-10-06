<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Api;

use Softaware\SocialLogin\Api\Data\LinkInterface;

/**
 * Stores the links between customers and provider identities.
 */
interface LinkRepositoryInterface
{
    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(LinkInterface $link): LinkInterface;

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $linkId): LinkInterface;

    /**
     * Finds the link for a provider identity. $websiteId is ignored when customer accounts are shared globally.
     */
    public function findByProviderUser(string $provider, string $providerUserId, int $websiteId): ?LinkInterface;

    public function findByCustomerAndProvider(int $customerId, string $provider): ?LinkInterface;

    /**
     * @return LinkInterface[]
     */
    public function getListByCustomerId(int $customerId): array;

    /**
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(LinkInterface $link): bool;

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $linkId): bool;
}
