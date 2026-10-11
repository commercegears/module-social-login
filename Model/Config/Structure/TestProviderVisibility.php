<?php

declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Config\Structure;

use Magento\Config\Model\Config\Structure\ElementVisibilityInterface;
use Magento\Framework\App\State as AppState;

/**
 * Shows the "Test Provider (Developers Only)" settings group only when Magento runs in developer mode, the only
 * mode in which the test provider works.
 */
class TestProviderVisibility implements ElementVisibilityInterface
{
    private const GROUP_PATH = 'commercegears_sociallogin/test';

    public function __construct(
        private readonly AppState $appState
    ) {
    }

    /**
     * @param string $path
     */
    public function isHidden($path): bool
    {
        $path = (string) $path;
        if ($path !== self::GROUP_PATH && !str_starts_with($path, self::GROUP_PATH . '/')) {
            return false;
        }
        return $this->appState->getMode() !== AppState::MODE_DEVELOPER;
    }

    /**
     * @param string $path
     */
    public function isDisabled($path): bool
    {
        return false;
    }
}
