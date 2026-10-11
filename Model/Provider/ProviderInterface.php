<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Provider;

use CommerceGears\SocialLogin\Model\AuthRequest;
use CommerceGears\SocialLogin\Model\Profile;

/**
 * An identity provider. Register new ones in di.xml on CommerceGears\SocialLogin\Model\Provider\ProviderPool.
 */
interface ProviderInterface
{
    /**
     * Lower-case code, also the config group id (commercegears_sociallogin/<code>/...).
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
     * @throws \CommerceGears\SocialLogin\Exception\SocialLoginException
     */
    public function fetchProfile(AuthRequest $request, array $callbackParams): Profile;
}
