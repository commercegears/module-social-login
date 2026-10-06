<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\ResourceModel\Link;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Softaware\SocialLogin\Model\Link;
use Softaware\SocialLogin\Model\ResourceModel\Link as LinkResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'link_id';

    protected function _construct()
    {
        $this->_init(Link::class, LinkResource::class);
    }
}
