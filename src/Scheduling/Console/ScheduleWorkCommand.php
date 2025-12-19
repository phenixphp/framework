<?php

declare(strict_types=1);

namespace Phenix\Scheduling\Console;

use Phenix\App;
use Phenix\Scheduling\ScheduleWorker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleWorkCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'schedule:work';

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Run the scheduled tasks in a long-running process';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ScheduleWorker $worker */
        $worker = App::make(ScheduleWorker::class);

        $worker->daemon($output);

        return Command::SUCCESS;
    }
}
