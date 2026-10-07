<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Provider;

use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\Http\HttpClient;
use Softaware\SocialLogin\Model\Jwt\JwksProvider;
use Softaware\SocialLogin\Model\Jwt\JwtValidator;

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
