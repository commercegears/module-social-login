<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Jwt;

/**
 * Converts a JSON Web Key (RSA or EC P-256) into a PEM public key usable by ext-openssl.
 */
class JwkConverter
{
    private const OID_RSA = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
    private const OID_EC_PUBLIC_KEY = "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
    private const OID_P256 = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";

    public function toPem(array $jwk): string
    {
        $kty = (string)($jwk['kty'] ?? '');
        if ($kty === 'RSA') {
            if (empty($jwk['n']) || empty($jwk['e'])) {
                throw new \InvalidArgumentException('RSA key without modulus or exponent.');
            }
            $publicKey = Der::sequence(
                Der::unsignedInteger(Base64Url::decode((string)$jwk['n'])),
                Der::unsignedInteger(Base64Url::decode((string)$jwk['e']))
            );
            return Der::toPem(Der::sequence(
                Der::sequence(self::OID_RSA, "\x05\x00"),
                Der::bitString($publicKey)
            ));
        }
        if ($kty === 'EC') {
            if (($jwk['crv'] ?? '') !== 'P-256' || empty($jwk['x']) || empty($jwk['y'])) {
                throw new \InvalidArgumentException('Only EC keys on curve P-256 are supported.');
            }
            $x = str_pad(Base64Url::decode((string)$jwk['x']), 32, "\x00", STR_PAD_LEFT);
            $y = str_pad(Base64Url::decode((string)$jwk['y']), 32, "\x00", STR_PAD_LEFT);
            return Der::toPem(Der::sequence(
                Der::sequence(self::OID_EC_PUBLIC_KEY, self::OID_P256),
                Der::bitString("\x04" . $x . $y)
            ));
        }
        throw new \InvalidArgumentException('Unsupported key type.');
    }
}
