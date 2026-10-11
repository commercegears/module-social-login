<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\ViewModel;

use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use CommerceGears\SocialLogin\Api\Data\LinkInterface;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use CommerceGears\SocialLogin\Model\Account\SocialAccountService;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;

/**
 * Data for My Account > Connected Accounts.
 */
class ConnectedAccounts implements ArgumentInterface
{
    private ?bool $hasPassword = null;

    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CustomerRegistry $customerRegistry,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly ProviderPool $providerPool,
        private readonly SocialAccountService $accountService,
        private readonly ProviderIcons $icons,
        private readonly UrlInterface $urlBuilder,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * One row per available provider, plus linked providers that have since been switched off.
     *
     * @return array<int, array{code: string, name: string, icon: string, link: ?LinkInterface, available: bool, can_unlink: bool}>
     */
    public function getRows(): array
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        $links = [];
        foreach ($this->linkRepository->getListByCustomerId($customerId) as $link) {
            $links[$link->getProvider()] = $link;
        }
        $rows = [];
        $available = $this->providerPool->getAvailable();
        foreach ($available as $code => $provider) {
            $rows[] = $this->row($code, $provider->getName(), $links[$code] ?? null, true, $customerId);
            unset($links[$code]);
        }
        foreach ($links as $code => $link) {
            $provider = $this->providerPool->get($code);
            $rows[] = $this->row($code, $provider ? $provider->getName() : $code, $link, false, $customerId);
        }
        return $rows;
    }

    public function hasPassword(): bool
    {
        if ($this->hasPassword === null) {
            $customerId = (int)$this->customerSession->getCustomerId();
            $this->hasPassword = $customerId > 0
                && (string)$this->customerRegistry->retrieveSecureData($customerId)->getPasswordHash() !== '';
        }
        return $this->hasPassword;
    }

    public function getConnectUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/auth/start');
    }

    public function getUnlinkUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/account/unlink');
    }

    public function getForgotPasswordUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account/forgotpassword');
    }

    public function getBackUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account');
    }

    public function formatDate(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        return $this->timezone->formatDate($date, \IntlDateFormatter::MEDIUM);
    }

    private function row(string $code, string $name, ?LinkInterface $link, bool $available, int $customerId): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'icon' => $this->icons->getSvg($code, 28),
            'link' => $link,
            'available' => $available,
            'can_unlink' => $link !== null && $this->accountService->canUnlink($customerId, $code, $this->hasPassword()),
        ];
    }
}
