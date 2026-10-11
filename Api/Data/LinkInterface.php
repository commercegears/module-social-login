<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Api\Data;

/**
 * A customer account linked to an identity at a provider.
 */
interface LinkInterface
{
    public const LINK_ID = 'link_id';
    public const CUSTOMER_ID = 'customer_id';
    public const WEBSITE_ID = 'website_id';
    public const PROVIDER = 'provider';
    public const PROVIDER_USER_ID = 'provider_user_id';
    public const PROVIDER_EMAIL = 'provider_email';
    public const DISPLAY_NAME = 'display_name';
    public const CREATED_AT = 'created_at';
    public const LAST_LOGIN_AT = 'last_login_at';
    public const LOGIN_COUNT = 'login_count';

    /**
     * @return int|null
     */
    public function getId();

    public function getCustomerId(): int;

    public function setCustomerId(int $customerId): self;

    public function getWebsiteId(): int;

    public function setWebsiteId(int $websiteId): self;

    public function getProvider(): string;

    public function setProvider(string $provider): self;

    public function getProviderUserId(): string;

    public function setProviderUserId(string $providerUserId): self;

    public function getProviderEmail(): ?string;

    public function setProviderEmail(?string $email): self;

    public function getDisplayName(): ?string;

    public function setDisplayName(?string $name): self;

    public function getCreatedAt(): ?string;

    public function getLastLoginAt(): ?string;

    public function setLastLoginAt(?string $date): self;

    public function getLoginCount(): int;

    public function setLoginCount(int $count): self;
}
