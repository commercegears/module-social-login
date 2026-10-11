<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\Account;

/**
 * Outcome of a social sign-in step, used by the controllers to pick the next page.
 */
class Result
{
    public const LOGGED_IN = 'logged_in';
    public const LINKED = 'linked';
    public const NEEDS_DETAILS = 'needs_details';
    public const NEEDS_PASSWORD = 'needs_password';
    public const NEEDS_CONFIRMATION = 'needs_confirmation';

    public function __construct(
        public readonly string $type,
        public readonly int $customerId = 0,
        public readonly bool $isNew = false,
        public readonly string $returnUrl = '',
        public readonly string $message = ''
    ) {
    }
}
