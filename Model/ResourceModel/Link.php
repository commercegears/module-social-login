<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Link extends AbstractDb
{
    public const TABLE = 'commercegears_social_login';

    protected function _construct()
    {
        $this->_init(self::TABLE, 'link_id');
    }
}
