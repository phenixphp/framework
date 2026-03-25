<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

class TimerRegistry
{
    /**
     * @var list<Timer>
     */
    protected static array $timers = [];

    public static function add(Timer $timer): void
    {
        self::$timers[] = $timer;
    }

    public static function run(): void
    {
        foreach (self::$timers as $timer) {
            $timer->run();
        }

        self::$timers = [];
    }
}
