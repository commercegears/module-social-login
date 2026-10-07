<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Inline SVG brand marks for the buttons, per provider code (di.xml argument "icons", which add-on modules
 * extend with their providers' marks). Marks that are black in the brand guidelines (such as X) use
 * currentColor so they turn white on dark buttons. Codes without an icon get a neutral person icon.
 */
class ProviderIcons implements ArgumentInterface
{
    public const GOOGLE = '<svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
    public const FACEBOOK = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#0866FF"/><path fill="#fff" d="M16.1 15.47l.53-3.47h-3.33V9.75c0-.95.47-1.88 1.95-1.88h1.51V4.92s-1.37-.23-2.69-.23c-2.74 0-4.53 1.66-4.53 4.67V12H6.5v3.47h3.04V24h3.76v-8.53h2.8z"/></svg>';
    public const X = '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>';
    public const TEST = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="#6B7280"/><path fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" d="M7.5 7.5h9M12 7.5v9.5"/></svg>';
    public const GENERIC = '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" fill="currentColor"/><path fill="currentColor" d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"/></svg>';

    /**
     * @param array<string, string> $icons Provider code => static, trusted SVG markup
     */
    public function __construct(
        private readonly array $icons = []
    ) {
    }

    /**
     * SVG markup (static, trusted) sized for the buttons.
     */
    public function getSvg(string $code, int $size = 20): string
    {
        $svg = $this->icons[$code] ?? '';
        if (!is_string($svg) || !str_starts_with($svg, '<svg ')) {
            $svg = self::GENERIC;
        }
        return str_replace(
            '<svg ',
            sprintf('<svg width="%1$d" height="%1$d" aria-hidden="true" focusable="false" ', $size),
            $svg
        );
    }
}
