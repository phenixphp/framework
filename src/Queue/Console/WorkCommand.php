<?php

declare(strict_types=1);

namespace Phenix\Queue\Console;

use Phenix\App;
use Phenix\Queue\Config;
use Phenix\Queue\Worker;
use Phenix\Queue\WorkerOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkCommand extends Command
{
    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'queue:work';

    /**
     * @var string
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Process the queue';

    protected function configure(): void
    {
        $this->setHelp('This command allows you to process the queue...')
            ->addArgument('connection', InputArgument::OPTIONAL, 'The name of the connection to use')
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'The name of the queue to process', 'default')
            ->addOption('once', 'o', InputOption::VALUE_NONE, 'Process the queue only once')
            ->addOption('chunks', null, InputOption::VALUE_NONE, 'Process the queue in chunks')
            ->addOption('sleep', 's', InputOption::VALUE_REQUIRED, 'The number of seconds to sleep when no tasks are available', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Worker $worker */
        $worker = App::make(Worker::class);

        $config = new Config();

        $connection = $input->getArgument('connection') ?? $config->getConnection();
        $queue = $input->getOption('queue');
        $method = $input->getOption('once') ? 'runOnce' : 'daemon';

        $worker->{$method}(
            $connection,
            $queue,
            new WorkerOptions(
                sleep: (int) $input->getOption('sleep'),
                chunkProcessing: $input->getOption('chunks')
            ),
            $output
        );

        return Command::SUCCESS;
    }
}
