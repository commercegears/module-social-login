<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Http;

use Magento\Framework\HTTP\Client\CurlFactory;
use Softaware\SocialLogin\Exception\SocialLoginException;

/**
 * Small JSON-over-HTTPS client for token, userinfo and JWKS endpoints.
 * Never logs request bodies (they contain secrets and codes).
 */
class HttpClient
{
    private const TIMEOUT = 15;
    private const USER_AGENT = 'Softaware-SocialLogin/1.0 (Magento 2)';

    public function __construct(
        private readonly CurlFactory $curlFactory
    ) {
    }

    /**
     * POST application/x-www-form-urlencoded and decode the JSON response.
     */
    public function postForm(string $url, array $fields, array $headers = []): array
    {
        $curl = $this->createClient($headers + ['Content-Type' => 'application/x-www-form-urlencoded']);
        try {
            $curl->post($url, $fields);
        } catch (\Exception $e) {
            throw SocialLoginException::withDetail(
                'We could not reach the sign-in provider. Please try again.',
                sprintf('POST %s failed: %s', $this->safeUrl($url), $e->getMessage())
            );
        }
        return $this->decode($url, $curl->getStatus(), (string)$curl->getBody());
    }

    public function getJson(string $url, array $headers = []): array
    {
        $curl = $this->createClient($headers);
        try {
            $curl->get($url);
        } catch (\Exception $e) {
            throw SocialLoginException::withDetail(
                'We could not reach the sign-in provider. Please try again.',
                sprintf('GET %s failed: %s', $this->safeUrl($url), $e->getMessage())
            );
        }
        return $this->decode($url, $curl->getStatus(), (string)$curl->getBody());
    }

    private function createClient(array $headers): \Magento\Framework\HTTP\Client\Curl
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout(self::TIMEOUT);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 10);
        $curl->setOption(CURLOPT_FOLLOWLOCATION, false);
        $curl->setOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        $curl->setHeaders($headers + [
            'Accept' => 'application/json',
            'User-Agent' => self::USER_AGENT,
        ]);
        return $curl;
    }

    private function decode(string $url, int $status, string $body): array
    {
        try {
            $data = $body === '' ? [] : json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $data = null;
        }
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            $error = is_array($data)
                ? trim(sprintf(
                    '%s %s',
                    is_scalar($data['error'] ?? null) ? $data['error'] : json_encode($data['error'] ?? ''),
                    is_scalar($data['error_description'] ?? null) ? $data['error_description'] : ''
                ))
                : 'non-JSON response';
            throw SocialLoginException::withDetail(
                'The sign-in provider rejected the request. Please try again.',
                sprintf('%s returned HTTP %d: %s', $this->safeUrl($url), $status, mb_substr($error, 0, 300))
            );
        }
        return $data;
    }

    /**
     * URL without query string (query may hold tokens).
     */
    private function safeUrl(string $url): string
    {
        return strtok($url, '?') ?: $url;
    }
}
