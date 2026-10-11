<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Model\SelfTest;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collects and prints the results of the self-test.
 */
class Reporter
{
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    public function section(string $title): void
    {
        $this->output->writeln('<info>' . $title . '</info>');
    }

    public function check(string $label, bool $ok): void
    {
        $ok ? $this->passed++ : $this->failed++;
        $this->output->writeln(sprintf('  %s %s', $ok ? '<info>PASS</info>' : '<error>FAIL</error>', $label));
    }

    public function note(string $text): void
    {
        $this->output->writeln('  ' . $text);
    }

    public function getPassed(): int
    {
        return $this->passed;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }
}
