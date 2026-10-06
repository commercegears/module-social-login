<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\ResourceModel\Link\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Admin grid collection: links joined with the customer's name and email.
 */
class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['customer' => $this->getTable('customer_entity')],
            'customer.entity_id = main_table.customer_id',
            [
                'customer_email' => 'customer.email',
                'customer_name' => new \Zend_Db_Expr("CONCAT_WS(' ', customer.firstname, customer.lastname)"),
            ]
        );
        $this->addFilterToMap('link_id', 'main_table.link_id');
        $this->addFilterToMap('website_id', 'main_table.website_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('customer_email', 'customer.email');
        $this->addFilterToMap('customer_name', new \Zend_Db_Expr("CONCAT_WS(' ', customer.firstname, customer.lastname)"));
        return $this;
    }
}
