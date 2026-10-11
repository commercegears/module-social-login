<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Framework\View\DesignInterface;

/**
 * Tells whether the current storefront theme is a Hyvä theme (Hyva/* or a child of one),
 * without depending on the Hyvä modules.
 */
class ThemeDetector
{
    private ?bool $isHyva = null;

    public function __construct(
        private readonly DesignInterface $design
    ) {
    }

    public function isHyva(): bool
    {
        if ($this->isHyva === null) {
            $this->isHyva = false;
            $theme = $this->design->getDesignTheme();
            $depth = 0;
            while ($theme && $depth++ < 10) {
                if (str_starts_with((string)$theme->getCode(), 'Hyva/')) {
                    $this->isHyva = true;
                    break;
                }
                $theme = $theme->getParentTheme();
            }
        }
        return $this->isHyva;
    }
}
