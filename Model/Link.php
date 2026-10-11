<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Framework\Model\AbstractModel;
use CommerceGears\SocialLogin\Api\Data\LinkInterface;
use CommerceGears\SocialLogin\Model\ResourceModel\Link as LinkResource;

class Link extends AbstractModel implements LinkInterface
{
    protected $_eventPrefix = 'commercegears_sociallogin_link';

    protected function _construct()
    {
        $this->_init(LinkResource::class);
    }

    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int $customerId): LinkInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getWebsiteId(): int
    {
        return (int)$this->getData(self::WEBSITE_ID);
    }

    public function setWebsiteId(int $websiteId): LinkInterface
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    public function getProvider(): string
    {
        return (string)$this->getData(self::PROVIDER);
    }

    public function setProvider(string $provider): LinkInterface
    {
        return $this->setData(self::PROVIDER, $provider);
    }

    public function getProviderUserId(): string
    {
        return (string)$this->getData(self::PROVIDER_USER_ID);
    }

    public function setProviderUserId(string $providerUserId): LinkInterface
    {
        return $this->setData(self::PROVIDER_USER_ID, $providerUserId);
    }

    public function getProviderEmail(): ?string
    {
        $value = $this->getData(self::PROVIDER_EMAIL);
        return $value === null ? null : (string)$value;
    }

    public function setProviderEmail(?string $email): LinkInterface
    {
        return $this->setData(self::PROVIDER_EMAIL, $email);
    }

    public function getDisplayName(): ?string
    {
        $value = $this->getData(self::DISPLAY_NAME);
        return $value === null ? null : (string)$value;
    }

    public function setDisplayName(?string $name): LinkInterface
    {
        return $this->setData(self::DISPLAY_NAME, $name);
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);
        return $value === null ? null : (string)$value;
    }

    public function getLastLoginAt(): ?string
    {
        $value = $this->getData(self::LAST_LOGIN_AT);
        return $value === null ? null : (string)$value;
    }

    public function setLastLoginAt(?string $date): LinkInterface
    {
        return $this->setData(self::LAST_LOGIN_AT, $date);
    }

    public function getLoginCount(): int
    {
        return (int)$this->getData(self::LOGIN_COUNT);
    }

    public function setLoginCount(int $count): LinkInterface
    {
        return $this->setData(self::LOGIN_COUNT, $count);
    }
}
