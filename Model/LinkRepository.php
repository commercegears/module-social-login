<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Customer\Model\Config\Share as ShareConfig;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use CommerceGears\SocialLogin\Api\Data\LinkInterface;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\ResourceModel\Link as LinkResource;
use CommerceGears\SocialLogin\Model\ResourceModel\Link\CollectionFactory;

class LinkRepository implements LinkRepositoryInterface
{
    public function __construct(
        private readonly LinkResource $resource,
        private readonly LinkFactory $linkFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly ShareConfig $shareConfig
    ) {
    }

    public function save(LinkInterface $link): LinkInterface
    {
        try {
            /** @var Link $link */
            $this->resource->save($link);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('The social login link could not be saved: %1', $e->getMessage()), $e);
        }
        return $link;
    }

    public function getById(int $linkId): LinkInterface
    {
        $link = $this->linkFactory->create();
        $this->resource->load($link, $linkId);
        if (!$link->getId()) {
            throw new NoSuchEntityException(__('Social login link %1 does not exist.', $linkId));
        }
        return $link;
    }

    public function findByProviderUser(string $provider, string $providerUserId, int $websiteId): ?LinkInterface
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(LinkInterface::PROVIDER, $provider)
            ->addFieldToFilter(LinkInterface::PROVIDER_USER_ID, $providerUserId);
        if ($this->shareConfig->isWebsiteScope()) {
            $collection->addFieldToFilter(LinkInterface::WEBSITE_ID, $websiteId);
        }
        $collection->setPageSize(1);
        $link = $collection->getFirstItem();
        return $link->getId() ? $link : null;
    }

    public function findByCustomerAndProvider(int $customerId, string $provider): ?LinkInterface
    {
        $link = $this->collectionFactory->create()
            ->addFieldToFilter(LinkInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(LinkInterface::PROVIDER, $provider)
            ->setPageSize(1)
            ->getFirstItem();
        return $link->getId() ? $link : null;
    }

    public function getListByCustomerId(int $customerId): array
    {
        return array_values($this->collectionFactory->create()
            ->addFieldToFilter(LinkInterface::CUSTOMER_ID, $customerId)
            ->setOrder(LinkInterface::CREATED_AT, 'ASC')
            ->getItems());
    }

    public function delete(LinkInterface $link): bool
    {
        try {
            /** @var Link $link */
            $this->resource->delete($link);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('The social login link could not be removed: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $linkId): bool
    {
        return $this->delete($this->getById($linkId));
    }
}
