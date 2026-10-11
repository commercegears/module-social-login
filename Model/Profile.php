<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

/**
 * Normalised identity returned by a provider after a successful authorisation.
 *
 * Two levels of trust in the email address:
 * - $emailVerified: the provider states that the address was verified. A new store account with this address
 *   needs no email confirmation.
 * - $emailTrusted: the provider is authoritative for the address now (e.g. a Gmail or Google Workspace
 *   address at Google). Only then may the identity sign in to an EXISTING store account with the same
 *   address without its password, or confirm an unconfirmed one. Defaults to $emailVerified.
 */
class Profile
{
    public readonly bool $emailTrusted;

    public function __construct(
        public readonly string $provider,
        public readonly string $providerUserId,
        public readonly ?string $email,
        public readonly bool $emailVerified,
        public readonly string $firstName = '',
        public readonly string $lastName = '',
        public readonly string $displayName = '',
        ?bool $emailTrusted = null
    ) {
        $this->emailTrusted = $emailVerified && ($emailTrusted ?? true);
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
            $this->displayName,
            $verified
        );
    }

    public function withNames(string $firstName, string $lastName): self
    {
        return new self(
            $this->provider,
            $this->providerUserId,
            $this->email,
            $this->emailVerified,
            $firstName,
            $lastName,
            $this->displayName,
            $this->emailTrusted
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
            'email_trusted' => $this->emailTrusted,
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
            (string)($data['display_name'] ?? ''),
            (bool)($data['email_trusted'] ?? false)
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
