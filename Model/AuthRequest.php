<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model;

/**
 * Data kept server-side between the redirect to the provider and the callback.
 */
class AuthRequest
{
    public const INTENT_LOGIN = 'login';
    public const INTENT_LINK = 'link';

    public function __construct(
        public readonly string $provider,
        public readonly string $state,
        public readonly string $nonce,
        public readonly string $codeVerifier,
        public readonly string $redirectUri,
        public readonly int $storeId,
        public readonly string $intent = self::INTENT_LOGIN,
        public readonly string $returnUrl = '',
        public readonly bool $popup = false,
        public readonly int $customerId = 0,
        public readonly string $sessionId = '',
        public readonly array $callbackParams = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'nonce' => $this->nonce,
            'code_verifier' => $this->codeVerifier,
            'redirect_uri' => $this->redirectUri,
            'store_id' => $this->storeId,
            'intent' => $this->intent,
            'return_url' => $this->returnUrl,
            'popup' => $this->popup,
            'customer_id' => $this->customerId,
            'session_id' => $this->sessionId,
            'callback_params' => $this->callbackParams,
        ];
    }

    public static function fromArray(string $state, array $data): self
    {
        return new self(
            (string)($data['provider'] ?? ''),
            $state,
            (string)($data['nonce'] ?? ''),
            (string)($data['code_verifier'] ?? ''),
            (string)($data['redirect_uri'] ?? ''),
            (int)($data['store_id'] ?? 0),
            (string)($data['intent'] ?? self::INTENT_LOGIN),
            (string)($data['return_url'] ?? ''),
            (bool)($data['popup'] ?? false),
            (int)($data['customer_id'] ?? 0),
            (string)($data['session_id'] ?? ''),
            is_array($data['callback_params'] ?? null) ? $data['callback_params'] : []
        );
    }

    public function withCallbackParams(array $params): self
    {
        $data = $this->toArray();
        $data['callback_params'] = $params;
        return self::fromArray($this->state, $data);
    }
}
