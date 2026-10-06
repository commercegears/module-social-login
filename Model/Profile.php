<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model;

/**
 * Normalised identity returned by a provider after a successful authorisation.
 */
class Profile
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerUserId,
        public readonly ?string $email,
        public readonly bool $emailVerified,
        public readonly string $firstName = '',
        public readonly string $lastName = '',
        public readonly string $displayName = ''
    ) {
    }

    public function withEmail(string $email, bool $verified): self
    {
        return new self(
            $this->provider,
            $this->providerUserId,
            $email,
            $verified,
            $this->firstName,
            $this->lastName,
            $this->displayName
        );
    }

    public function hasEmail(): bool
    {
        return $this->email !== null && $this->email !== '';
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_user_id' => $this->providerUserId,
            'email' => $this->email,
            'email_verified' => $this->emailVerified,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'display_name' => $this->displayName,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['provider'] ?? ''),
            (string)($data['provider_user_id'] ?? ''),
            isset($data['email']) && $data['email'] !== '' ? (string)$data['email'] : null,
            (bool)($data['email_verified'] ?? false),
            (string)($data['first_name'] ?? ''),
            (string)($data['last_name'] ?? ''),
            (string)($data['display_name'] ?? '')
        );
    }

    /**
     * Splits "Ada Lovelace" into first and last name when a provider only returns a full name.
     *
     * @return array{0: string, 1: string}
     */
    public static function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            return ['', ''];
        }
        $parts = explode(' ', $name);
        $last = count($parts) > 1 ? (string)array_pop($parts) : '';
        return [implode(' ', $parts), $last];
    }
}
