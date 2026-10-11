<?php
/**
 * CommerceGears Social Login
 *
 * @copyright Copyright (c) CommerceGears (https://www.commercegearscommerce.co.uk/)
 */
declare(strict_types=1);

namespace CommerceGears\SocialLogin\Console\Command;

use Magento\Framework\Exception\LocalizedException;
use CommerceGears\SocialLogin\Api\LinkRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Removes a link by ID (the customer account is kept).
 */
class UnlinkCommand extends Command
{
    public function __construct(
        private readonly LinkRepositoryInterface $linkRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('commercegears:social-login:unlink')
            ->setDescription('Removes a social login link by its ID (see commercegears:social-login:links).')
            ->addArgument('link_id', InputArgument::REQUIRED, 'Link ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $link = $this->linkRepository->getById((int)$input->getArgument('link_id'));
            $this->linkRepository->delete($link);
        } catch (LocalizedException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
        $output->writeln(sprintf('Removed the %s link of customer %d.', $link->getProvider(), $link->getCustomerId()));
        return Command::SUCCESS;
    }
}
