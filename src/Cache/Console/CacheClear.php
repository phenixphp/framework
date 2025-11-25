<?php

declare(strict_types=1);

namespace Phenix\Cache\Console;

use Phenix\Facades\Cache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClear extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'cache:clear';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Clear cached data in default cache store';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to clear cached data in the default cache store.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Cache::clear();

        $output->writeln('<info>Cached data cleared successfully!</info>');

        return Command::SUCCESS;
    }
}
