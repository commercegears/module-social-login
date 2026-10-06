<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\Account;

/**
 * What to do with a provider identity, as decided by AccountMatcher.
 */
class Decision
{
    /** Identity already linked: sign in that customer. */
    public const LOGIN_LINKED = 'login_linked';
    /** Verified email of an existing customer and auto-linking is on: link, then sign in. */
    public const LINK_EXISTING = 'link_existing';
    /** Existing customer, but email unverified or auto-linking off: ask for the store password. */
    public const ASK_PASSWORD = 'ask_password';
    /** No email (or no name) from the provider: ask the customer to complete the details. */
    public const ASK_DETAILS = 'ask_details';
    /** No matching customer: create one. */
    public const CREATE = 'create';

    public function __construct(
        public readonly string $type,
        public readonly int $customerId = 0
    ) {
    }
}
