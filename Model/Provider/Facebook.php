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
 * Facebook Login (manual OAuth flow) and the Graph API "me" endpoint, signed with appsecret_proof.
 * Facebook only returns an email address the person has confirmed, so a new store account needs no
 * email confirmation. The Graph API has no verification flag and Facebook is not the mailbox provider,
 * so the address is not trusted to sign in to an existing store account without its password.
 */
class Facebook extends AbstractProvider
{
    private const DEFAULT_VERSION = 'v26.0';

    public function getCode(): string
    {
        return 'facebook';
    }

    public function getName(): string
    {
        return 'Facebook';
    }

    private function getVersion(?int $storeId): string
    {
        $version = $this->context->config->getProviderValue('facebook', 'graph_version', $storeId);
        return preg_match('/^v\d{1,2}\.\d$/', $version) === 1 ? $version : self::DEFAULT_VERSION;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return 'https://www.facebook.com/' . $this->getVersion($storeId) . '/dialog/oauth';
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return 'https://graph.facebook.com/' . $this->getVersion($storeId) . '/oauth/access_token';
    }

    protected function getScopes(?int $storeId): array
    {
        return ['email', 'public_profile'];
    }

    protected function getExtraAuthorizationParams(AuthRequest $request): array
    {
        return ['scope' => 'email,public_profile'];
    }

    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile
    {
        $token = $this->exchangeCode($request, $callbackParams);
        $accessToken = (string)($token['access_token'] ?? '');
        $storeId = $request->storeId;
        $query = http_build_query([
            'fields' => 'id,name,first_name,last_name,email',
            'access_token' => $accessToken,
            'appsecret_proof' => hash_hmac('sha256', $accessToken, $this->getClientSecret($storeId)),
        ]);
        $me = $this->context->httpClient->getJson(
            'https://graph.facebook.com/' . $this->getVersion($storeId) . '/me?' . $query
        );
        if (empty($me['id'])) {
            throw $this->profileError('Graph API response without id');
        }
        $email = isset($me['email']) && $me['email'] !== '' ? (string)$me['email'] : null;
        return new Profile(
            $this->getCode(),
            (string)$me['id'],
            $email,
            $email !== null,
            (string)($me['first_name'] ?? ''),
            (string)($me['last_name'] ?? ''),
            (string)($me['name'] ?? ''),
            false
        );
    }
}
