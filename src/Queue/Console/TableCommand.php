<?php

declare(strict_types=1);

namespace Phenix\Queue\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TableCommand extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'queue:table';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates the database table for the queue.';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to create the database table for the queue.');

        $this->addArgument('name', InputArgument::OPTIONAL, 'The table name');

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileName = "20250101205638_create_tasks_table";

        $input->setArgument('name', $fileName);

        return parent::execute($input, $output);
    }

    protected function outputDirectory(): string
    {
        return 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    protected function stub(): string
    {
        return 'queue_table.stub';
    }

    protected function commonName(): string
    {
        return 'Queue table';
    }
}
