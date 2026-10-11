<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Provider;

use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Profile;

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
        $email = isset($claims['email']) && $claims['email'] !== '' ? (string)$claims['email'] : null;
        $verified = $email !== null && self::isTrue($claims['email_verified'] ?? false);
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            $email,
            $verified,
            $first,
            $last,
            (string)($claims['name'] ?? ''),
            $verified && self::isAuthoritative((string)$email, (string)($claims['hd'] ?? ''))
        );
    }

    /**
     * Google is only authoritative for Gmail addresses and Google Workspace accounts ("hd" claim). Other
     * addresses were verified once when the Google account was created, but may have changed hands since
     * (Google's guidance: ask for another proof before linking them to an existing account).
     */
    public static function isAuthoritative(string $email, string $hostedDomain): bool
    {
        $domain = strtolower((string)substr(strrchr($email, '@') ?: '', 1));
        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            return true;
        }
        // Workspace accounts carry "hd"; the address may be on one of the organisation's secondary domains.
        return $hostedDomain !== '';
    }
}
