<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\ViewModel;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Data for the consent page of the test provider (developer mode only).
 */
class TestAuthorize implements ArgumentInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getPostUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/test/authorize');
    }

    public function getParam(string $name): string
    {
        $value = $this->request->getParam($name);
        return is_string($value) ? $value : '';
    }
}
