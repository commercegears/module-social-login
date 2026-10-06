<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Provider;

use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Profile;

/**
 * Google Identity (OpenID Connect) with PKCE.
 */
class Google extends AbstractOidcProvider
{
    public function getCode(): string
    {
        return 'google';
    }

    public function getName(): string
    {
        return 'Google';
    }

    public function usesPkce(): bool
    {
        return true;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function getJwksUri(?int $storeId): string
    {
        return 'https://www.googleapis.com/oauth2/v3/certs';
    }

    protected function getIssuerExpectation(?int $storeId): array
    {
        return ['issuer' => ['https://accounts.google.com', 'accounts.google.com']];
    }

    protected function getScopes(?int $storeId): array
    {
        return ['openid', 'email', 'profile'];
    }

    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return ['prompt' => 'select_account', 'access_type' => 'online'];
    }

    protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile
    {
        [$first, $last] = $this->namesFromClaims($claims);
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            isset($claims['email']) ? (string)$claims['email'] : null,
            self::isTrue($claims['email_verified'] ?? false),
            $first,
            $last,
            (string)($claims['name'] ?? '')
        );
    }
}
