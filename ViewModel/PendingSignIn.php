<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\ViewModel;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use CommerceGears\SocialLogin\Model\Account\PendingStorage;
use CommerceGears\SocialLogin\Model\Profile;
use CommerceGears\SocialLogin\Model\Provider\ProviderPool;

/**
 * Data for the "Complete your account" and "Connect your account" steps.
 */
class PendingSignIn implements ArgumentInterface
{
    private ?array $pending = null;

    public function __construct(
        private readonly PendingStorage $pendingStorage,
        private readonly ProviderPool $providerPool,
        private readonly ProviderIcons $icons,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function getProfile(): ?Profile
    {
        return $this->getPending()['profile'] ?? null;
    }

    public function getProviderName(): string
    {
        $profile = $this->getProfile();
        $provider = $profile ? $this->providerPool->get($profile->provider) : null;
        return $provider ? $provider->getName() : '';
    }

    public function getProviderIcon(): string
    {
        $profile = $this->getProfile();
        return $profile ? $this->icons->getSvg($profile->provider, 28) : '';
    }

    public function needsEmail(): bool
    {
        $profile = $this->getProfile();
        return $profile !== null && !$profile->hasEmail();
    }

    /**
     * Email of the existing store account (for the password step).
     */
    public function getAccountEmail(): string
    {
        $customerId = (int)($this->getPending()['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return '';
        }
        try {
            return (string)$this->customerRepository->getById($customerId)->getEmail();
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getCompletePostUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/account/completePost');
    }

    public function getConfirmPostUrl(): string
    {
        return $this->urlBuilder->getUrl('sociallogin/account/confirmPost');
    }

    public function getForgotPasswordUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account/forgotpassword');
    }

    public function getLoginUrl(): string
    {
        return $this->urlBuilder->getUrl('customer/account/login');
    }

    private function getPending(): array
    {
        if ($this->pending === null) {
            $this->pending = $this->pendingStorage->get() ?? [];
        }
        return $this->pending;
    }
}
