<?php

declare(strict_types=1);

namespace Phenix\Scheduling\Console;

use Phenix\App;
use Phenix\Scheduling\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRunCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultName = 'schedule:run';

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected static $defaultDescription = 'Run the scheduled tasks once and exit';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Schedule $schedule */
        $schedule = App::make(Schedule::class);

        $schedule->run();

        return Command::SUCCESS;
    }
}
