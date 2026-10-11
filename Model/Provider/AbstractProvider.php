<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Provider;

use CommerceGears\SocialLogin\Exception\SocialLoginException;
use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Jwt\Base64Url;

/**
 * OAuth 2.0 authorisation code flow (RFC 6749) with optional PKCE (RFC 7636).
 */
abstract class AbstractProvider implements ProviderInterface
{
    public function __construct(
        protected readonly ProviderContext $context
    ) {
    }

    abstract protected function getAuthorizeEndpoint(?int $storeId): string;

    abstract protected function getTokenEndpoint(?int $storeId): string;

    /**
     * @return string[]
     */
    abstract protected function getScopes(?int $storeId): array;

    public function usesPkce(): bool
    {
        return false;
    }

    public function usesFormPost(): bool
    {
        return false;
    }

    protected function usesNonce(): bool
    {
        return false;
    }

    /**
     * Send client credentials as HTTP Basic auth instead of in the body.
     */
    protected function usesBasicAuth(): bool
    {
        return false;
    }

    public function isAvailable(?int $storeId = null): bool
    {
        return $this->context->config->isProviderEnabled($this->getCode(), $storeId)
            && $this->getClientId($storeId) !== ''
            && $this->hasClientSecret($storeId);
    }

    protected function hasClientSecret(?int $storeId): bool
    {
        return $this->getClientSecret($storeId) !== '';
    }

    protected function getClientId(?int $storeId): string
    {
        return $this->context->config->getProviderValue($this->getCode(), 'client_id', $storeId);
    }

    protected function getClientSecret(?int $storeId): string
    {
        return $this->context->config->getProviderSecret($this->getCode(), 'client_secret', $storeId);
    }

    /**
     * Provider-specific additions to the authorisation request.
     */
    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return [];
    }

    public function getAuthorizationUrl(AuthRequest $request): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->getClientId($request->storeId),
            'redirect_uri' => $request->redirectUri,
            'scope' => implode(' ', $this->getScopes($request->storeId)),
            'state' => $request->state,
        ];
        if ($this->usesNonce()) {
            $params['nonce'] = $request->nonce;
        }
        if ($this->usesPkce()) {
            $params['code_challenge'] = self::codeChallenge($request->codeVerifier);
            $params['code_challenge_method'] = 'S256';
        }
        $params = array_merge($params, $this->getExtraAuthorizationParams($request));
        $endpoint = $this->getAuthorizeEndpoint($request->storeId);

        return $endpoint . (str_contains($endpoint, '?') ? '&' : '?')
            . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public static function codeChallenge(string $verifier): string
    {
        return Base64Url::encode(hash('sha256', $verifier, true));
    }

    /**
     * Exchanges the authorisation code at the token endpoint.
     */
    protected function exchangeCode(AuthRequest $request, array $callbackParams): array
    {
        $code = (string)($callbackParams['code'] ?? '');
        if ($code === '' || strlen($code) > 2048) {
            throw SocialLoginException::withDetail(
                'The sign-in provider did not return an authorisation code.',
                sprintf('%s: callback without code', $this->getCode())
            );
        }
        $storeId = $request->storeId;
        $fields = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $request->redirectUri,
            'client_id' => $this->getClientId($storeId),
        ];
        $headers = [];
        if ($this->usesBasicAuth()) {
            $headers['Authorization'] = 'Basic ' . base64_encode(
                rawurlencode($this->getClientId($storeId)) . ':' . rawurlencode($this->getClientSecret($storeId))
            );
        } else {
            $fields['client_secret'] = $this->getClientSecret($storeId);
        }
        if ($this->usesPkce()) {
            $fields['code_verifier'] = $request->codeVerifier;
        }
        $response = $this->context->httpClient->postForm($this->getTokenEndpoint($storeId), $fields, $headers);
        if (empty($response['access_token']) && empty($response['id_token'])) {
            throw SocialLoginException::withDetail(
                'The sign-in provider did not return a token.',
                sprintf('%s: token response without access_token/id_token', $this->getCode())
            );
        }
        return $response;
    }

    protected static function isTrue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === 'true' || $value === '1';
    }

    protected function profileError(string $detail): SocialLoginException
    {
        return SocialLoginException::withDetail(
            'We could not read your profile from the sign-in provider.',
            $this->getCode() . ': ' . $detail
        );
    }
}
