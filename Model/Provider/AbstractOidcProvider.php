<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Provider;

use CommerceGears\SocialLogin\Exception\SocialLoginException;
use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Jwt\JwtException;
use CommerceGears\SocialLogin\Model\Profile;

/**
 * OpenID Connect: the identity comes from the ID token, whose signature is checked against
 * the provider's JWKS, together with issuer, audience, expiry and nonce.
 */
abstract class AbstractOidcProvider extends AbstractProvider
{
    abstract protected function getJwksUri(?int $storeId): string;

    /**
     * Issuer expectation for JwtValidator: ['issuer' => ...] or ['issuer_pattern' => ...].
     */
    abstract protected function getIssuerExpectation(?int $storeId): array;

    abstract protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile;

    protected function usesNonce(): bool
    {
        return true;
    }

    /**
     * When false, the nonce is only compared if the provider echoes it.
     */
    protected function isNonceRequired(): bool
    {
        return true;
    }

    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile
    {
        $tokenResponse = $this->exchangeCode($request, $callbackParams);
        $idToken = (string)($tokenResponse['id_token'] ?? '');
        if ($idToken === '') {
            throw $this->profileError('token response without id_token');
        }
        $claims = $this->validateIdToken($idToken, $request);
        return $this->buildProfile($claims, $tokenResponse, $request, $callbackParams);
    }

    public function validateIdToken(string $idToken, AuthRequest $request): array
    {
        try {
            $kid = $this->context->jwtValidator->getKeyId($idToken);
            $jwks = $this->context->jwksProvider->getKeySet($this->getJwksUri($request->storeId), $kid);
            return $this->context->jwtValidator->validate($idToken, $jwks, $this->getIssuerExpectation($request->storeId) + [
                'audience' => $this->getClientId($request->storeId),
                'nonce' => $this->usesNonce() ? $request->nonce : null,
                'nonce_required' => $this->usesNonce() && $this->isNonceRequired(),
                'algorithms' => ['RS256', 'ES256'],
            ]);
        } catch (JwtException $e) {
            throw SocialLoginException::withDetail(
                'The sign-in could not be verified. Please try again.',
                sprintf('%s: ID token rejected: %s', $this->getCode(), $e->getMessage())
            );
        }
    }

    protected function namesFromClaims(array $claims): array
    {
        $first = trim((string)($claims['given_name'] ?? ''));
        $last = trim((string)($claims['family_name'] ?? ''));
        if ($first === '' && $last === '' && !empty($claims['name'])) {
            [$first, $last] = Profile::splitName((string)$claims['name']);
        }
        return [$first, $last];
    }
}
