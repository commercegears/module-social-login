<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\SocialLogin\Model\Account\Result;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;

/**
 * Builds the response at the end of a step: a redirect, or in popup mode a tiny page that hands
 * the target URL to the opening window and closes the popup.
 */
class FlowResponder
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RedirectResolver $redirectResolver,
        private readonly MessageManager $messageManager,
        private readonly UrlInterface $urlBuilder,
        private readonly AssetRepository $assetRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProviderPool $providerPool,
        private readonly Escaper $escaper
    ) {
    }

    public function forResult(Result $result, string $providerCode, bool $popup): ResultInterface
    {
        $provider = $this->providerPool->get($providerCode);
        $providerName = $provider ? $provider->getName() : $providerCode;
        switch ($result->type) {
            case Result::LOGGED_IN:
                if ($result->isNew) {
                    $this->messageManager->addSuccessMessage(
                        __('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
                    );
                }
                $url = $this->redirectResolver->getSuccessUrl($result->returnUrl);
                break;
            case Result::LINKED:
                $this->messageManager->addSuccessMessage(__('Your %1 account is connected.', $providerName));
                $url = $this->redirectResolver->getConnectedAccountsUrl();
                break;
            case Result::NEEDS_DETAILS:
                if ($result->message !== '') {
                    $this->messageManager->addErrorMessage($result->message);
                }
                $url = $this->urlBuilder->getUrl('sociallogin/account/complete');
                break;
            case Result::NEEDS_PASSWORD:
                $url = $this->urlBuilder->getUrl('sociallogin/account/confirm');
                break;
            default:
                $this->messageManager->addNoticeMessage(
                    __('You must confirm your account. Please check your email for the confirmation link.')
                );
                $url = $this->redirectResolver->getLoginUrl();
        }
        return $this->go($url, $popup);
    }

    /**
     * @param string $message Shown to the customer; '' when another module has already explained the problem
     */
    public function forError(string $message, bool $popup, bool $isLink): ResultInterface
    {
        if ($message !== '') {
            $this->messageManager->addErrorMessage($message);
        }
        return $this->go(
            $isLink ? $this->redirectResolver->getConnectedAccountsUrl() : $this->redirectResolver->getLoginUrl(),
            $popup
        );
    }

    public function go(string $url, bool $popup): ResultInterface
    {
        if (!$popup) {
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl($url);
            return $redirect;
        }
        $script = $this->assetRepository->getUrlWithParams(
            'CommerceGears_SocialLogin::js/popup-callback.js',
            ['_secure' => true]
        );
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>' . $this->escaper->escapeHtml(__('Signing you in…')) . '</title></head><body>'
            . '<p><a id="sw-social-done" href="' . $this->escaper->escapeUrl($url) . '">'
            . $this->escaper->escapeHtml(__('Continue')) . '</a></p>'
            . '<script src="' . $this->escaper->escapeUrl($script) . '"></script>'
            . '</body></html>';
        /** @var Raw $raw */
        $raw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $raw->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $raw->setHeader('Cache-Control', 'no-store', true);
        $raw->setContents($html);
        return $raw;
    }
}
