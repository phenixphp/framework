<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Phenix\Facades\Schedule;
use Phenix\Util\Date;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleWorker
{
    protected bool $quit = false;

    public function daemon(OutputInterface|null $output = null): void
    {
        $output?->writeln('<info>Starting schedule worker...</info>');

        $this->listenSignals();

        $lastRunKey = null;

        while (true) {
            if ($this->shouldQuit()) {
                break;
            }

            $this->sleepMicroseconds(100_000); // 100ms

            $now = $this->now();

            if ($now->second !== 0) {
                continue;
            }

            $currentKey = $now->format('Y-m-d H:i');

            if ($currentKey === $lastRunKey) {
                continue;
            }

            Schedule::run();

            $lastRunKey = $currentKey;
        }

        $output?->writeln('<info>Schedule worker stopped.</info>');
    }

    public function shouldQuit(): bool
    {
        return $this->quit;
    }

    protected function sleepMicroseconds(int $microseconds): void
    {
        usleep($microseconds);
    }

    protected function now(): Date
    {
        return Date::now('UTC');
    }

    protected function listenSignals(): void
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function (): void {
            $this->quit = true;
        });

        pcntl_signal(SIGTERM, function (): void {
            $this->quit = true;
        });
    }
}
