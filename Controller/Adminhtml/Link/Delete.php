<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Adminhtml\Link;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;

/**
 * Removes one link (the customer account itself is not touched).
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_SocialLogin::links_delete';

    public function __construct(
        Context $context,
        private readonly LinkRepositoryInterface $linkRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $returnTo = (int)$this->getRequest()->getParam('customer_id');
        try {
            $this->linkRepository->deleteById((int)$this->getRequest()->getParam('id'));
            $this->messageManager->addSuccessMessage(__('The social login link has been removed.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $returnTo > 0
            ? $redirect->setPath('customer/index/edit', ['id' => $returnTo])
            : $redirect->setPath('*/*/index');
    }
}
