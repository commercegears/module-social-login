<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Jwt;

/**
 * Minimal ASN.1 DER helpers: enough to build public keys from JWKs and to convert
 * ECDSA signatures between DER and the raw R||S form used by JOSE.
 */
class Der
{
    public static function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    public static function sequence(string ...$items): string
    {
        $body = implode('', $items);
        return "\x30" . self::length(strlen($body)) . $body;
    }

    public static function unsignedInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . self::length(strlen($bytes)) . $bytes;
    }

    public static function bitString(string $bytes): string
    {
        $body = "\x00" . $bytes;
        return "\x03" . self::length(strlen($body)) . $body;
    }

    public static function toPem(string $der, string $label = 'PUBLIC KEY'): string
    {
        return '-----BEGIN ' . $label . "-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . '-----END ' . $label . "-----\n";
    }

    /**
     * Raw R||S (JOSE) signature to DER SEQUENCE { INTEGER r, INTEGER s } (OpenSSL).
     */
    public static function rawToDerSignature(string $raw, int $partLength = 32): string
    {
        if (strlen($raw) !== 2 * $partLength) {
            throw new \InvalidArgumentException('Invalid ECDSA signature length.');
        }
        return self::sequence(
            self::unsignedInteger(substr($raw, 0, $partLength)),
            self::unsignedInteger(substr($raw, $partLength))
        );
    }

    /**
     * DER ECDSA signature (OpenSSL) to raw R||S (JOSE).
     */
    public static function derToRawSignature(string $der, int $partLength = 32): string
    {
        $offset = 0;
        if (($der[$offset++] ?? '') !== "\x30") {
            throw new \InvalidArgumentException('Invalid DER signature.');
        }
        self::readLength($der, $offset);
        $parts = [];
        for ($i = 0; $i < 2; $i++) {
            if (($der[$offset++] ?? '') !== "\x02") {
                throw new \InvalidArgumentException('Invalid DER signature.');
            }
            $len = self::readLength($der, $offset);
            $int = ltrim(substr($der, $offset, $len), "\x00");
            $offset += $len;
            if (strlen($int) > $partLength) {
                throw new \InvalidArgumentException('Invalid DER signature.');
            }
            $parts[] = str_pad($int, $partLength, "\x00", STR_PAD_LEFT);
        }
        return $parts[0] . $parts[1];
    }

    private static function readLength(string $der, int &$offset): int
    {
        $byte = ord($der[$offset++] ?? "\x00");
        if ($byte < 0x80) {
            return $byte;
        }
        $count = $byte & 0x7f;
        $length = 0;
        for ($i = 0; $i < $count; $i++) {
            $length = ($length << 8) | ord($der[$offset++] ?? "\x00");
        }
        return $length;
    }
}
