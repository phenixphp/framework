<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Closure;
use Phenix\Util\Date;

class Schedule
{
    /**
     * @var array<Scheduler>
     */
    protected array $schedules = [];

    public function timer(Closure $closure): Timer
    {
        $timer = new Timer($closure);

        TimerRegistry::add($timer);

        return $timer;
    }

    public function call(Closure $closure): Scheduler
    {
        $scheduler = new Scheduler($closure);

        $this->schedules[] = $scheduler;

        return $scheduler;
    }

    public function run(): void
    {
        $now = null;
        foreach ($this->schedules as $scheduler) {
            $now ??= Date::now('UTC');

            $scheduler->tick($now);
        }
    }
}
