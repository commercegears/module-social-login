<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Provider;

use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Profile;

/**
 * An identity provider. Register new ones in di.xml on Softaware\SocialLogin\Model\Provider\ProviderPool.
 */
interface ProviderInterface
{
    /**
     * Lower-case code, also the config group id (softaware_sociallogin/<code>/...).
     */
    public function getCode(): string;

    /**
     * Brand name shown when no custom label is configured, e.g. "Google".
     */
    public function getName(): string;

    /**
     * Enabled for the store and all required credentials are filled in.
     */
    public function isAvailable(?int $storeId = null): bool;

    public function usesPkce(): bool;

    /**
     * True when the provider posts the callback (response_mode=form_post).
     */
    public function usesFormPost(): bool;

    /**
     * URL of the provider's consent screen for this request.
     */
    public function getAuthorizationUrl(AuthRequest $request): string;

    /**
     * Exchanges the authorisation code and returns the verified identity.
     *
     * @param array $callbackParams Request parameters received on the callback
     * @throws \Softaware\SocialLogin\Exception\SocialLoginException
     */
    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile;
}
