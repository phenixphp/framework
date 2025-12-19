<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;
use Phenix\Scheduling\Scheduler;
use Phenix\Scheduling\Timer;

/**
 * @method static Timer timer(Closure $closure)
 * @method static Scheduler call(Closure $closure)
 * @method static void run()
 *
 * @see \Phenix\Scheduling\Schedule
 */
class Schedule extends Facade
{
    protected static function getKeyName(): string
    {
        return \Phenix\Scheduling\Schedule::class;
    }
}
