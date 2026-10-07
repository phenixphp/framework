<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Closure;
use InvalidArgumentException;
use Phenix\Scheduling\Contracts\ScheduleLock;
use Phenix\Util\Date;

class Schedule
{
    /** @var array<string, Scheduler> */
    protected array $schedules = [];

    public function __construct(protected ScheduleLock $lock, protected int $lockTtl = 86400)
    {
    }

    public function call(string $name, Closure $closure): Scheduler
    {
        if (isset($this->schedules[$name])) {
            throw new InvalidArgumentException("Schedule [{$name}] is already registered.");
        }

        return $this->schedules[$name] = new Scheduler($name, $closure, $this->lock, $this->lockTtl);
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
