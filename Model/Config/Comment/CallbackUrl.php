<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\SocialLogin\Model\CallbackUrl as CallbackUrlBuilder;

/**
 * Shows the exact redirect/callback URL to register at the provider, for the store view selected
 * in the configuration scope switcher (default store view on default/website scope).
 */
class CallbackUrl implements CommentInterface
{
    public function __construct(
        private readonly CallbackUrlBuilder $callbackUrl,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly Escaper $escaper,
        private readonly string $providerCode = '',
        private readonly string $hint = ''
    ) {
    }

    public function getCommentText($elementValue)
    {
        try {
            $store = $this->resolveStore();
            $url = $this->callbackUrl->get($this->providerCode, (int)$store->getId());
        } catch (\Exception $e) {
            return '';
        }
        $text = (string)__('Redirect / callback URL to register at the provider (store view "%1"):', $store->getName());
        $html = $this->escaper->escapeHtml($text)
            . '<br><code style="user-select:all;word-break:break-all">' . $this->escaper->escapeHtml($url) . '</code>';
        if ($this->hint !== '') {
            $html .= '<br>' . $this->escaper->escapeHtml(__($this->hint));
        }
        return $html;
    }

    private function resolveStore(): \Magento\Store\Api\Data\StoreInterface
    {
        $storeId = (int)$this->request->getParam('store');
        if ($storeId > 0) {
            return $this->storeManager->getStore($storeId);
        }
        $websiteId = (int)$this->request->getParam('website');
        if ($websiteId > 0) {
            return $this->storeManager->getWebsite($websiteId)->getDefaultStore();
        }
        return $this->storeManager->getDefaultStoreView();
    }
}
