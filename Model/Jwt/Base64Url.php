<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Jwt;

/**
 * RFC 4648 section 5 encoding without padding, as used by JOSE and PKCE.
 */
class Base64Url
{
    public static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function decode(string $data): string
    {
        if (preg_match('/^[A-Za-z0-9_\-]*={0,2}$/', $data) !== 1) {
            throw new \InvalidArgumentException('Invalid base64url data.');
        }
        $data = rtrim($data, '=');
        $remainder = strlen($data) % 4;
        if ($remainder === 1) {
            throw new \InvalidArgumentException('Invalid base64url length.');
        }
        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64url data.');
        }
        return $decoded;
    }
}
