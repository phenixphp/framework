<?php

declare(strict_types=1);

namespace Phenix\Console\Commands;

use Phenix\Facades\View;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewClear extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'view:clear';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Clear all compiled view files';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to clean all compiled view files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        View::clearCache();

        $output->writeln('<info>Compiled views cleared successfully!.</info>');

        return Command::SUCCESS;
    }
}
