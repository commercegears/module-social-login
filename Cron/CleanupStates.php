<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Cron;

use Softaware\SocialLogin\Model\StateStore;

/**
 * Removes abandoned sign-in requests (older than 15 minutes).
 */
class CleanupStates
{
    public function __construct(
        private readonly StateStore $stateStore
    ) {
    }

    public function execute(): void
    {
        $this->stateStore->deleteExpired();
    }
}
