<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Provider;

use CommerceGears\SocialLogin\Model\Config;
use CommerceGears\SocialLogin\Model\Http\HttpClient;
use CommerceGears\SocialLogin\Model\Jwt\JwksProvider;
use CommerceGears\SocialLogin\Model\Jwt\JwtValidator;

/**
 * Shared dependencies of all providers. A provider that needs more declares its own constructor
 * arguments and passes the context on to parent::__construct().
 */
class ProviderContext
{
    public function __construct(
        public readonly Config $config,
        public readonly HttpClient $httpClient,
        public readonly JwksProvider $jwksProvider,
        public readonly JwtValidator $jwtValidator
    ) {
    }
}
