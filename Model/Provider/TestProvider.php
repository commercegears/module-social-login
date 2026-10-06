<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Provider;

use Magento\Framework\App\State as AppState;
use Magento\Framework\FlagManager;
use Magento\Framework\UrlInterface;
use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Jwt\Base64Url;
use Softaware\SocialLogin\Model\Profile;

/**
 * TEST ONLY. A local identity provider for trying the whole flow without real provider apps.
 *
 * - Works only when Magento runs in developer mode, and only when switched on in the config.
 * - Its email addresses are never treated as verified, so it can never sign in to an existing
 *   account without that account's password.
 * - Issues RS256 ID tokens signed with a locally generated key and validates them through the
 *   same JWKS code path as Google, Microsoft, Apple and LinkedIn.
 */
class TestProvider extends AbstractOidcProvider
{
    public const ISSUER = 'urn:softaware:sociallogin:test-idp';
    public const CLIENT_ID = 'softaware-sociallogin-test';
    public const JWKS_URI = 'local://softaware-sociallogin-test/jwks';
    private const FLAG_KEY = 'softaware_sociallogin_test_key';
    private const KEY_ID = 'sw-test-1';

    public function __construct(
        ProviderContext $context,
        private readonly AppState $appState,
        private readonly FlagManager $flagManager,
        private readonly UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
    }

    public function getCode(): string
    {
        return 'test';
    }

    public function getName(): string
    {
        return 'Test (dev)';
    }

    public function isAvailable(?int $storeId = null): bool
    {
        return $this->appState->getMode() === AppState::MODE_DEVELOPER
            && $this->context->config->isProviderEnabled('test', $storeId);
    }

    protected function getClientId(?int $storeId): string
    {
        return self::CLIENT_ID;
    }

    protected function getAuthorizeEndpoint(?int $storeId): string
    {
        return $this->urlBuilder->getUrl('sociallogin/test/authorize', ['_nosid' => true]);
    }

    protected function getTokenEndpoint(?int $storeId): string
    {
        return '';
    }

    protected function getJwksUri(?int $storeId): string
    {
        $this->context->jwksProvider->setStatic(self::JWKS_URI, $this->getPublicJwks());
        return self::JWKS_URI;
    }

    protected function getIssuerExpectation(?int $storeId): array
    {
        return ['issuer' => self::ISSUER];
    }

    protected function getScopes(?int $storeId): array
    {
        return ['openid', 'email', 'profile'];
    }

    /**
     * The "authorisation code" of the test provider is the signed ID token itself.
     */
    protected function exchangeCode(AuthRequest $request, array $callbackParams): array
    {
        return ['id_token' => (string)($callbackParams['code'] ?? '')];
    }

    protected function buildProfile(array $claims, array $tokenResponse, AuthRequest $request, array $callbackParams): Profile
    {
        $email = isset($claims['email']) && $claims['email'] !== '' ? (string)$claims['email'] : null;
        return new Profile(
            $this->getCode(),
            (string)$claims['sub'],
            $email,
            false,
            (string)($claims['given_name'] ?? ''),
            (string)($claims['family_name'] ?? ''),
            trim(($claims['given_name'] ?? '') . ' ' . ($claims['family_name'] ?? ''))
        );
    }

    /**
     * Signs an ID token for the test consent page.
     */
    public function issueIdToken(string $subject, ?string $email, string $firstName, string $lastName, string $nonce): string
    {
        $now = time();
        $claims = array_filter([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT_ID,
            'sub' => $subject,
            'iat' => $now,
            'exp' => $now + 300,
            'nonce' => $nonce,
            'email' => $email,
            'email_verified' => false,
            'given_name' => $firstName,
            'family_name' => $lastName,
        ], static fn ($value) => $value !== null && $value !== '');
        $claims['email_verified'] = false;
        $input = Base64Url::encode((string)json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::KEY_ID]))
            . '.' . Base64Url::encode((string)json_encode($claims, JSON_UNESCAPED_SLASHES));
        $signature = '';
        openssl_sign($input, $signature, $this->getPrivateKey(), OPENSSL_ALGO_SHA256);
        return $input . '.' . Base64Url::encode($signature);
    }

    public function getPublicJwks(): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->getPrivateKey()));
        return ['keys' => [[
            'kty' => 'RSA',
            'kid' => self::KEY_ID,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => Base64Url::encode($details['rsa']['n']),
            'e' => Base64Url::encode($details['rsa']['e']),
        ]]];
    }

    private function getPrivateKey(): string
    {
        $pem = (string)$this->flagManager->getFlagData(self::FLAG_KEY);
        if ($pem === '' || openssl_pkey_get_private($pem) === false) {
            $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
            openssl_pkey_export($key, $pem);
            $this->flagManager->saveFlag(self::FLAG_KEY, $pem);
        }
        return $pem;
    }
}
