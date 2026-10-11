<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use CommerceGears\SocialLogin\Api\Data\LinkInterface;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;
use CommerceGears\SocialLogin\ViewModel\ProviderIcons;

/**
 * "Social Login" tab on the customer edit page: providers linked to this customer.
 */
class SocialTab extends Template implements TabInterface
{
    protected $_template = 'CommerceGears_SocialLogin::customer/tab.phtml';

    public function __construct(
        Context $context,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly ProviderPool $providerPool,
        private readonly ProviderIcons $icons,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCustomerId(): int
    {
        return (int)$this->getRequest()->getParam('id');
    }

    /**
     * @return LinkInterface[]
     */
    public function getLinks(): array
    {
        return $this->getCustomerId() > 0 ? $this->linkRepository->getListByCustomerId($this->getCustomerId()) : [];
    }

    public function getProviderName(string $code): string
    {
        $provider = $this->providerPool->get($code);
        return $provider ? $provider->getName() : $code;
    }

    public function getIcon(string $code): string
    {
        return $this->icons->getSvg($code, 20);
    }

    public function canRemove(): bool
    {
        return $this->_authorization->isAllowed('CommerceGears_SocialLogin::links_delete');
    }

    public function getRemoveUrl(LinkInterface $link): string
    {
        return $this->getUrl('commercegears_sociallogin/link/delete', [
            'id' => $link->getId(),
            'customer_id' => $this->getCustomerId(),
        ]);
    }

    public function formatDateTime(?string $date): string
    {
        return $date ? $this->formatDate($date, \IntlDateFormatter::MEDIUM, true) : '—';
    }

    public function getTabLabel()
    {
        return __('Social Login');
    }

    public function getTabTitle()
    {
        return __('Social Login');
    }

    public function getTabClass()
    {
        return '';
    }

    public function getTabUrl()
    {
        return '';
    }

    public function isAjaxLoaded()
    {
        return false;
    }

    public function canShowTab()
    {
        return $this->getCustomerId() > 0 && $this->_authorization->isAllowed('CommerceGears_SocialLogin::links');
    }

    public function isHidden()
    {
        return false;
    }
}
