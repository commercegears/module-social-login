<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Jwt;

/**
 * Validates a compact JWS (ID token): signature against a JWK set, then the standard claims.
 *
 * Supported algorithms: RS256 and ES256. "none" and HMAC algorithms are always rejected.
 */
class JwtValidator
{
    private const ALGORITHMS = [
        'RS256' => ['kty' => 'RSA'],
        'ES256' => ['kty' => 'EC'],
    ];

    public function __construct(
        private readonly JwkConverter $jwkConverter
    ) {
    }

    /**
     * Splits a token and returns [header, claims] without verifying anything.
     *
     * @return array{0: array, 1: array}
     */
    public function decodeUnverified(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new JwtException('Token is not a compact JWS.');
        }
        try {
            $header = json_decode(Base64Url::decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
            $claims = json_decode(Base64Url::decode($parts[1]), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException | \InvalidArgumentException $e) {
            throw new JwtException('Token header or payload cannot be decoded.');
        }
        if (!is_array($header) || !is_array($claims)) {
            throw new JwtException('Token header or payload is not an object.');
        }
        return [$header, $claims];
    }

    /**
     * Returns the "kid" from the token header (or '' when absent).
     */
    public function getKeyId(string $jwt): string
    {
        [$header] = $this->decodeUnverified($jwt);
        return (string)($header['kid'] ?? '');
    }

    /**
     * @param array $jwks JWK set: ['keys' => [...]]
     * @param array $expect Keys: issuer (string|string[]), issuer_pattern (regex), audience (string),
     *                      nonce (?string), nonce_required (bool), algorithms (string[]), leeway (int), now (int)
     * @return array Verified claims
     */
    public function validate(string $jwt, array $jwks, array $expect): array
    {
        [$header, $claims] = $this->decodeUnverified($jwt);
        [$encodedHeader, $encodedPayload, $encodedSignature] = explode('.', $jwt);

        $algorithm = (string)($header['alg'] ?? '');
        $allowed = $expect['algorithms'] ?? ['RS256'];
        if (!isset(self::ALGORITHMS[$algorithm]) || !in_array($algorithm, $allowed, true)) {
            throw new JwtException(sprintf('Algorithm "%s" is not accepted.', $algorithm));
        }

        $jwk = $this->selectKey($jwks, (string)($header['kid'] ?? ''), self::ALGORITHMS[$algorithm]['kty'], $algorithm);
        $signature = Base64Url::decode($encodedSignature);
        if ($algorithm === 'ES256') {
            $signature = Der::rawToDerSignature($signature);
        }
        $publicKey = openssl_pkey_get_public($this->jwkConverter->toPem($jwk));
        if ($publicKey === false) {
            throw new JwtException('Signing key cannot be loaded.');
        }
        $verified = openssl_verify($encodedHeader . '.' . $encodedPayload, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new JwtException('Token signature is invalid.');
        }

        $this->validateClaims($claims, $expect);
        return $claims;
    }

    private function selectKey(array $jwks, string $kid, string $kty, string $algorithm): array
    {
        $candidates = [];
        foreach ($jwks['keys'] ?? [] as $key) {
            if (!is_array($key) || ($key['kty'] ?? '') !== $kty) {
                continue;
            }
            if (isset($key['use']) && $key['use'] !== 'sig') {
                continue;
            }
            if (isset($key['alg']) && $key['alg'] !== $algorithm) {
                continue;
            }
            if ($kid !== '' && ($key['kid'] ?? '') !== $kid) {
                continue;
            }
            $candidates[] = $key;
        }
        if (count($candidates) === 1) {
            return $candidates[0];
        }
        throw new JwtException($candidates === []
            ? sprintf('No signing key found for kid "%s".', $kid)
            : 'Token has no kid and the key set holds several keys.');
    }

    private function validateClaims(array $claims, array $expect): void
    {
        $now = (int)($expect['now'] ?? time());
        $leeway = (int)($expect['leeway'] ?? 120);

        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int)$claims['exp'] < $now - $leeway) {
            throw new JwtException('Token has expired.');
        }
        if (isset($claims['nbf']) && (int)$claims['nbf'] > $now + $leeway) {
            throw new JwtException('Token is not valid yet.');
        }
        if (isset($claims['iat']) && (int)$claims['iat'] > $now + $leeway) {
            throw new JwtException('Token was issued in the future.');
        }

        $issuer = (string)($claims['iss'] ?? '');
        if (isset($expect['issuer'])) {
            if (!in_array($issuer, (array)$expect['issuer'], true)) {
                throw new JwtException(sprintf('Unexpected issuer "%s".', $issuer));
            }
        } elseif (isset($expect['issuer_pattern'])) {
            if (preg_match($expect['issuer_pattern'], $issuer) !== 1) {
                throw new JwtException(sprintf('Unexpected issuer "%s".', $issuer));
            }
        } else {
            throw new JwtException('No issuer expectation configured.');
        }

        $audience = (string)($expect['audience'] ?? '');
        $tokenAudience = (array)($claims['aud'] ?? []);
        if ($audience === '' || !in_array($audience, $tokenAudience, true)) {
            throw new JwtException('Token was issued for a different client.');
        }
        if (count($tokenAudience) > 1 && isset($claims['azp']) && $claims['azp'] !== $audience) {
            throw new JwtException('Authorised party does not match the client.');
        }

        $nonce = $expect['nonce'] ?? null;
        $nonceRequired = (bool)($expect['nonce_required'] ?? false);
        if ($nonce !== null && ($nonceRequired || isset($claims['nonce']))) {
            if (!is_string($claims['nonce'] ?? null) || !hash_equals((string)$nonce, $claims['nonce'])) {
                throw new JwtException('Nonce does not match.');
            }
        }

        if (!isset($claims['sub']) || !is_scalar($claims['sub']) || (string)$claims['sub'] === '') {
            throw new JwtException('Token has no subject.');
        }
    }
}
