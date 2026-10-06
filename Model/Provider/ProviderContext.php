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
use Softaware\SocialLogin\Model\Jwt\Es256Signer;
use Softaware\SocialLogin\Model\Jwt\JwksProvider;
use Softaware\SocialLogin\Model\Jwt\JwtValidator;

/**
 * Shared dependencies of all providers (keeps provider constructors empty).
 */
class ProviderContext
{
    public function __construct(
        public readonly Config $config,
        public readonly HttpClient $httpClient,
        public readonly JwksProvider $jwksProvider,
        public readonly JwtValidator $jwtValidator,
        public readonly Es256Signer $es256Signer
    ) {
    }
}
