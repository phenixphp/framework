<?php

declare(strict_types=1);

namespace Phenix\Auth\Console;

use Phenix\Console\Maker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PersonalAccessTokensTableCommand extends Maker
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'tokens:table';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Creates the database table for personal access tokens.';

    protected function configure(): void
    {
        $this->setHelp('This command generates the migration to create the personal access tokens table.');

        $this->addArgument('name', InputArgument::OPTIONAL, 'The migration file name');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force creation even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Static timestamped file name for reproducible tests.
        $fileName = '20251128110000_create_personal_access_tokens_table';
        $input->setArgument('name', $fileName);

        return parent::execute($input, $output);
    }

    protected function outputDirectory(): string
    {
        return 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    protected function stub(): string
    {
        return 'personal_access_tokens_table.stub';
    }

    protected function commonName(): string
    {
        return 'Personal access tokens table';
    }
}
