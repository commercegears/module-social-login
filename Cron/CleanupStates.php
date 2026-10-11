<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://commercegears.com/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Cron;

use CommerceGears\SocialLogin\Model\StateStore;

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
