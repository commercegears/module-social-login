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
 * X (Twitter) OAuth 2.0 with PKCE. Works as a confidential client (client secret, HTTP Basic auth)
 * or as a public client (no secret). X returns no email unless the app has the email permission and
 * "Request email" is switched on; without it the customer is asked for an email address.
 */
class X extends AbstractProvider
{
    public function getCode(): string
    {
        return 'twitter';
    }

    public function getName(): string
    {
        return 'X';
    }

    public function usesPkce(): bool
    {
        return true;
    }

    protected function usesBasicAuth(): bool
    {
        return true;
    }

    protected function hasClientSecret(?int $storeId): bool
    {
        return true;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://x.com/i/oauth2/authorize';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://api.x.com/2/oauth2/token';
    }

    private function requestsEmail(?int $storeId): bool
    {
        return $this->context->config->getProviderValue('twitter', 'request_email', $storeId) === '1';
    }

    protected function getScopes(?int $storeId): array
    {
        $scopes = ['users.read', 'tweet.read'];
        if ($this->requestsEmail($storeId)) {
            $scopes[] = 'users.email';
        }
        return $scopes;
    }

    protected function exchangeCode(AuthRequest $request, array $callbackParams): array
    {
        if ($this->getClientSecret($request->storeId) === '') {
            // Public client: PKCE only, client_id in the body.
            $code = (string)($callbackParams['code'] ?? '');
            if ($code === '') {
                throw $this->profileError('callback without code');
            }
            return $this->context->httpClient->postForm($this->getTokenEndpoint($request->storeId), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $request->redirectUri,
                'client_id' => $this->getClientId($request->storeId),
                'code_verifier' => $request->codeVerifier,
            ]);
        }
        return parent::exchangeCode($request, $callbackParams);
    }

    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile
    {
        $token = $this->exchangeCode($request, $callbackParams);
        $fields = $this->requestsEmail($request->storeId) ? '?user.fields=confirmed_email' : '';
        $me = $this->context->httpClient->getJson(
            'https://api.x.com/2/users/me' . $fields,
            ['Authorization' => 'Bearer ' . $token['access_token']]
        );
        $data = $me['data'] ?? [];
        if (empty($data['id'])) {
            throw $this->profileError('users/me response without id');
        }
        $email = isset($data['confirmed_email']) && $data['confirmed_email'] !== '' ? (string)$data['confirmed_email'] : null;
        $name = trim((string)($data['name'] ?? ''));
        [$first, $last] = Profile::splitName($name !== '' ? $name : (string)($data['username'] ?? ''));
        return new Profile(
            $this->getCode(),
            (string)$data['id'],
            $email,
            $email !== null,
            $first,
            $last,
            $name !== '' ? $name : '@' . ($data['username'] ?? '')
        );
    }
}
