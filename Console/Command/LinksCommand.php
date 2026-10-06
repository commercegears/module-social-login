<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Console\Command;

use Softaware\SocialLogin\Model\ResourceModel\Link\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists social login links.
 */
class LinksCommand extends Command
{
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('softaware:social-login:links')
            ->setDescription('Lists customer accounts linked to sign-in providers.')
            ->addOption('customer', 'c', InputOption::VALUE_REQUIRED, 'Customer ID or email')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'Provider code, e.g. google')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum rows', '50');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $collection = $this->collectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['customer' => $collection->getTable('customer_entity')],
            'customer.entity_id = main_table.customer_id',
            ['customer_email' => 'customer.email']
        );
        $customer = (string)$input->getOption('customer');
        if ($customer !== '') {
            ctype_digit($customer)
                ? $collection->addFieldToFilter('main_table.customer_id', (int)$customer)
                : $collection->getSelect()->where('customer.email = ?', $customer);
        }
        if ($input->getOption('provider')) {
            $collection->addFieldToFilter('main_table.provider', (string)$input->getOption('provider'));
        }
        $collection->setOrder('main_table.link_id', 'DESC')->setPageSize(max(1, (int)$input->getOption('limit')));

        $rows = [];
        foreach ($collection as $link) {
            $rows[] = [
                $link->getId(),
                $link->getData('customer_id'),
                $link->getData('customer_email'),
                $link->getData('provider'),
                $link->getData('provider_email'),
                $link->getData('created_at'),
                $link->getData('last_login_at'),
                $link->getData('login_count'),
            ];
        }
        if ($rows === []) {
            $output->writeln('No links found.');
            return Command::SUCCESS;
        }
        (new Table($output))
            ->setHeaders(['Link ID', 'Customer ID', 'Customer email', 'Provider', 'Email at provider', 'Linked (UTC)', 'Last sign-in (UTC)', 'Sign-ins'])
            ->setRows($rows)
            ->render();
        return Command::SUCCESS;
    }
}
