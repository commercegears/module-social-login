<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Controller\Adminhtml\Link;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\ResourceModel\Link\CollectionFactory;

/**
 * Removes the selected links.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_SocialLogin::links_delete';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly LinkRepositoryInterface $linkRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection->getItems() as $link) {
            $this->linkRepository->delete($link);
            $count++;
        }
        $this->messageManager->addSuccessMessage(
            $count === 1
                ? __('1 social login link has been removed.')
                : __('%1 social login links have been removed.', $count)
        );
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
