<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Exception;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Raised when a sign-in cannot be completed. The message is safe to show to the customer;
 * technical details go to the log via getLogDetail().
 */
class SocialLoginException extends LocalizedException
{
    private string $logDetail = '';

    public static function withDetail(string|Phrase $customerMessage, string $logDetail): static
    {
        // phpcs:ignore Magento2.Exceptions.DirectThrow
        $exception = new static($customerMessage instanceof Phrase ? $customerMessage : __($customerMessage));
        $exception->logDetail = $logDetail;
        return $exception;
    }

    public function getLogDetail(): string
    {
        return $this->logDetail !== '' ? $this->logDetail : $this->getMessage();
    }
}
