<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Ui\Component\Listing\Column;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Row actions: open the customer, remove the link.
 */
class LinkActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $canDelete = $this->authorization->isAllowed('CommerceGears_SocialLogin::links_delete');
        foreach ($dataSource['data']['items'] as &$item) {
            $name = $this->getData('name');
            $item[$name]['customer'] = [
                'href' => $this->urlBuilder->getUrl('customer/index/edit', ['id' => $item['customer_id']]),
                'label' => __('Open Customer'),
            ];
            if ($canDelete) {
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl('commercegears_sociallogin/link/delete', ['id' => $item['link_id']]),
                    'label' => __('Remove Link'),
                    'post' => true,
                    'confirm' => [
                        'title' => __('Remove link'),
                        'message' => __('Remove this link? The customer account is kept.'),
                    ],
                ];
            }
        }
        unset($item);
        return $dataSource;
    }
}
