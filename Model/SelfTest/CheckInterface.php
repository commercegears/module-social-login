<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Model\SelfTest;

/**
 * Additional checks for bin/magento softaware:social-login:self-test, e.g. from a module that adds providers.
 * Register implementations in di.xml in the "checks" argument of
 * Softaware\SocialLogin\Console\Command\SelfTestCommand.
 *
 * Checks must not contact any provider and must clean up whatever they create.
 */
interface CheckInterface
{
    /**
     * Runs the checks: start with $reporter->section('Title'), then $reporter->check('label', $passed) per check.
     */
    public function run(Reporter $reporter): void;
}
