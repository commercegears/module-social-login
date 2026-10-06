<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model;

use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The redirect / callback URL to register with each provider (one per store view URL).
 * Always built with the storefront URL model, also when called in the admin (config comments).
 */
class CallbackUrl
{
    public const ROUTE = 'sociallogin/auth/callback';

    /**
     * @param UrlFactory $urlFactory Creates Magento\Framework\Url (frontend), see di.xml
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlFactory $urlFactory
    ) {
    }

    public function get(string $providerCode, ?int $storeId = null): string
    {
        $store = $this->storeManager->getStore($storeId);
        return $this->urlFactory->create()
            ->setScope($store)
            ->getUrl(self::ROUTE, [
                'provider' => $providerCode,
                '_nosid' => true,
                '_secure' => true,
            ]);
    }
}
