<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Test;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use CommerceGears\SocialLogin\Model\CallbackUrl;
use CommerceGears\SocialLogin\Model\Provider\TestProvider;

/**
 * TEST ONLY consent page of the local test provider (developer mode + switched on in config).
 * GET shows a form to choose the identity; POST (form key checked by Magento) returns to the callback.
 */
class Authorize implements HttpGetActionInterface, HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly ForwardFactory $forwardFactory,
        private readonly TestProvider $testProvider,
        private readonly CallbackUrl $callbackUrl
    ) {
    }

    public function execute()
    {
        if (!$this->testProvider->isAvailable()) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        $redirectUri = (string)$this->request->getParam('redirect_uri');
        if ($redirectUri !== $this->callbackUrl->get('test')) {
            return $this->forwardFactory->create()->forward('noroute');
        }
        if (!$this->request->isPost()) {
            $page = $this->pageFactory->create();
            $page->getConfig()->getTitle()->set(__('Test provider (developer mode only)'));
            $page->getConfig()->setRobots('NOINDEX,NOFOLLOW');
            return $page;
        }

        $state = (string)$this->request->getParam('state');
        if ($this->request->getParam('decision') === 'deny') {
            return $this->redirectFactory->create()->setUrl(
                $redirectUri . '?' . http_build_query(['error' => 'access_denied', 'state' => $state])
            );
        }
        $email = trim((string)$this->request->getParam('email'));
        $token = $this->testProvider->issueIdToken(
            mb_substr(trim((string)$this->request->getParam('subject')) ?: 'test-user', 0, 64),
            $email !== '' ? $email : null,
            mb_substr(trim((string)$this->request->getParam('firstname')), 0, 64),
            mb_substr(trim((string)$this->request->getParam('lastname')), 0, 64),
            (string)$this->request->getParam('nonce')
        );
        return $this->redirectFactory->create()->setUrl(
            $redirectUri . '?' . http_build_query(['code' => $token, 'state' => $state])
        );
    }
}
