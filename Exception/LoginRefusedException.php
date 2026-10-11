<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Exception;

/**
 * Another module refused to sign the customer in (e.g. the account is waiting for approval).
 * When that module already told the customer why, no second message is shown.
 */
class LoginRefusedException extends SocialLoginException
{
    private bool $explained = false;

    public static function forCustomer(int $customerId, bool $explained): self
    {
        $exception = self::withDetail(
            'We could not sign you in to this account.',
            sprintf('customer %d not signed in after setCustomerDataAsLoggedIn (refused by another module)', $customerId)
        );
        $exception->explained = $explained;
        return $exception;
    }

    /**
     * True when the refusing module has already added a message for the customer.
     */
    public function isExplained(): bool
    {
        return $this->explained;
    }
}
