<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use CommerceGears\SocialLogin\Model\Jwt\Base64Url;

/**
 * Pending authorisation requests, stored server-side (not in the PHP session, which a cross-site
 * form_post callback does not carry). Rows are keyed by SHA-256 of the state,
 * bound to the browser cookie, single-use and expire after 15 minutes.
 */
class StateStore
{
    public const TABLE = 'commercegears_social_login_state';
    public const TTL = 900;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EncryptorInterface $encryptor,
        private readonly Json $json
    ) {
    }

    public static function randomToken(int $bytes = 32): string
    {
        return Base64Url::encode(random_bytes($bytes));
    }

    public function save(AuthRequest $request, string $binding): void
    {
        $connection = $this->resource->getConnection();
        $connection->insertOnDuplicate(
            $this->resource->getTableName(self::TABLE),
            [
                'state_hash' => hash('sha256', $request->state),
                'binding_hash' => hash('sha256', $binding),
                'provider' => $request->provider,
                'payload' => $this->encryptor->encrypt($this->json->serialize($request->toArray())),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ],
            ['binding_hash', 'provider', 'payload', 'created_at']
        );
    }

    /**
     * Reads without consuming; null when unknown, expired or bound to another browser.
     */
    public function peek(string $state, string $binding): ?AuthRequest
    {
        $row = $this->load($state);
        if ($row === null || !$this->isValid($row, $binding)) {
            return null;
        }
        return $this->toRequest($state, $row);
    }

    /**
     * Reads and deletes; a state can be used once only.
     */
    public function consume(string $state, string $binding): ?AuthRequest
    {
        $row = $this->load($state);
        if ($row === null) {
            return null;
        }
        $deleted = $this->resource->getConnection()->delete(
            $this->resource->getTableName(self::TABLE),
            ['state_hash = ?' => hash('sha256', $state)]
        );
        if ($deleted !== 1 || !$this->isValid($row, $binding)) {
            return null;
        }
        return $this->toRequest($state, $row);
    }

    public function deleteExpired(): int
    {
        $connection = $this->resource->getConnection();
        return $connection->delete(
            $this->resource->getTableName(self::TABLE),
            ['created_at < ?' => gmdate('Y-m-d H:i:s', time() - self::TTL)]
        );
    }

    private function load(string $state): ?array
    {
        if (preg_match('/^[A-Za-z0-9_\-]{20,128}$/', $state) !== 1) {
            return null;
        }
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE))
            ->where('state_hash = ?', hash('sha256', $state));
        $row = $connection->fetchRow($select);
        return is_array($row) ? $row : null;
    }

    private function isValid(array $row, string $binding): bool
    {
        if ($binding === '' || !hash_equals((string)$row['binding_hash'], hash('sha256', $binding))) {
            return false;
        }
        $created = strtotime((string)$row['created_at'] . ' UTC');
        return $created !== false && $created >= time() - self::TTL;
    }

    private function toRequest(string $state, array $row): ?AuthRequest
    {
        try {
            $data = $this->json->unserialize((string)$this->encryptor->decrypt((string)$row['payload']));
        } catch (\InvalidArgumentException $e) {
            return null;
        }
        if (!is_array($data) || ($data['provider'] ?? '') !== $row['provider']) {
            return null;
        }
        return AuthRequest::fromArray($state, $data);
    }
}
