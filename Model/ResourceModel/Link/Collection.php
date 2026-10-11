<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\ResourceModel\Link;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use CommerceGears\SocialLogin\Model\Link;
use CommerceGears\SocialLogin\Model\ResourceModel\Link as LinkResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'link_id';

    protected function _construct()
    {
        $this->_init(Link::class, LinkResource::class);
    }
}
