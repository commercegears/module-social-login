<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Config\Source;

use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Customer groups (without NOT LOGGED IN), with "use the store's default group" first.
 */
class CustomerGroup implements OptionSourceInterface
{
    public function __construct(
        private readonly GroupManagementInterface $groupManagement
    ) {
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '0', 'label' => __('Default group of the store (Customer Configuration)')]];
        foreach ($this->groupManagement->getLoggedInGroups() as $group) {
            $options[] = ['value' => (string)$group->getId(), 'label' => $group->getCode()];
        }
        return $options;
    }
}
