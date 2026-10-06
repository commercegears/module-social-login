<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Controller\Adminhtml\Link;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Softaware\SocialLogin\Api\LinkRepositoryInterface;
use Softaware\SocialLogin\Model\ResourceModel\Link\CollectionFactory;

/**
 * Removes the selected links.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_SocialLogin::links_delete';

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
        $this->messageManager->addSuccessMessage(__('%1 social login link(s) have been removed.', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
