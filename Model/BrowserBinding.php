<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model;

use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Softaware\SocialLogin\Model\Jwt\Base64Url;

/**
 * Random value in a short-lived cookie that ties a pending authorisation to the browser that
 * started it (protects the callback against login CSRF). SameSite=None so that it also arrives
 * with cross-site form_post callbacks (response_mode=form_post).
 */
class BrowserBinding
{
    public const COOKIE = 'sw_social_bind';
    private const LIFETIME = 1800;

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory
    ) {
    }

    public function get(): string
    {
        $value = (string)$this->cookieManager->getCookie(self::COOKIE, '');
        return preg_match('/^[A-Za-z0-9_\-]{43}$/', $value) === 1 ? $value : '';
    }

    public function getOrCreate(): string
    {
        $value = $this->get();
        if ($value === '') {
            $value = Base64Url::encode(random_bytes(32));
        }
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(self::LIFETIME)
            ->setPath('/')
            ->setSecure(true)
            ->setHttpOnly(true)
            ->setSameSite('None');
        $this->cookieManager->setPublicCookie(self::COOKIE, $value, $metadata);
        return $value;
    }
}
