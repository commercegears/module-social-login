<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Jwt;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\Serializer\Json;
use CommerceGears\SocialLogin\Model\Http\HttpClient;

/**
 * Fetches and caches a provider's JWK set. Refetches once when a token names an unknown key
 * (providers rotate keys), but not more than once every few minutes.
 */
class JwksProvider
{
    private const CACHE_PREFIX = 'commercegears_sociallogin_jwks_';
    private const CACHE_TAG = 'COMMERCEGEARS_SOCIALLOGIN_JWKS';
    private const LIFETIME = 21600;
    private const MIN_REFRESH_INTERVAL = 300;

    /** @var array<string, array> */
    private array $static = [];

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly CacheInterface $cache,
        private readonly Json $json
    ) {
    }

    /**
     * Registers a key set for a URL without fetching it (used by the test provider and the self-test).
     */
    public function setStatic(string $url, array $jwks): void
    {
        $this->static[$url] = $jwks;
    }

    public function getKeySet(string $url, string $kid = ''): array
    {
        if (isset($this->static[$url])) {
            return $this->static[$url];
        }
        $cacheKey = self::CACHE_PREFIX . hash('sha256', $url);
        $cached = $this->cache->load($cacheKey);
        $entry = $cached ? $this->json->unserialize($cached) : null;

        if (is_array($entry) && isset($entry['jwks'])
            && ($kid === '' || $this->hasKid($entry['jwks'], $kid)
                || time() - (int)$entry['fetched_at'] < self::MIN_REFRESH_INTERVAL)
        ) {
            return $entry['jwks'];
        }

        $jwks = $this->httpClient->getJson($url);
        if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
            throw new JwtException('The key set has no "keys" array.');
        }
        $this->cache->save(
            $this->json->serialize(['jwks' => $jwks, 'fetched_at' => time()]),
            $cacheKey,
            [self::CACHE_TAG],
            self::LIFETIME
        );
        return $jwks;
    }

    private function hasKid(array $jwks, string $kid): bool
    {
        foreach ($jwks['keys'] ?? [] as $key) {
            if (($key['kid'] ?? null) === $kid) {
                return true;
            }
        }
        return false;
    }
}
