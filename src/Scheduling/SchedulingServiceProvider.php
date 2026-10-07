<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Phenix\Facades\Config;
use Phenix\Facades\File;
use Phenix\Facades\Redis;
use Phenix\Providers\ServiceProvider;
use Phenix\Scheduling\Console\ScheduleRunCommand;
use Phenix\Scheduling\Console\ScheduleWorkCommand;
use Phenix\Scheduling\Contracts\ScheduleLock;

class SchedulingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(ScheduleLock::class, fn (): ScheduleLock => new RedisScheduleLock(
            Redis::connection((string) Config::get('schedule.connection', 'default'))->client(),
            (string) Config::get('schedule.lock_prefix', 'phenix:schedule:')
        ))->setShared(true);

        $this->bind(Schedule::class, fn (): Schedule => new Schedule(
            $this->getContainer()->get(ScheduleLock::class),
            (int) Config::get('schedule.occurrence_ttl', 86400)
        ))->setShared(true);

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
