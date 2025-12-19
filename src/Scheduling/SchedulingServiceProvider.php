<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Phenix\Facades\File;
use Phenix\Providers\ServiceProvider;
use Phenix\Scheduling\Console\ScheduleRunCommand;
use Phenix\Scheduling\Console\ScheduleWorkCommand;

class SchedulingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(Schedule::class)->setShared(true);
        $this->bind(ScheduleWorker::class);

        $this->commands([
            ScheduleWorkCommand::class,
            ScheduleRunCommand::class,
        ]);

        $this->loadSchedules();
    }

    private function loadSchedules(): void
    {
        $schedulePath = base_path('schedule' . DIRECTORY_SEPARATOR . 'schedules.php');

        if (File::exists($schedulePath)) {
            require $schedulePath;
        }
    }
}
