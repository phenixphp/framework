<?php

declare(strict_types=1);

namespace Phenix\Scheduling\Contracts;

interface ScheduleLock
{
    public function acquire(string $schedule, string $occurrence, int $ttl): string|null;

    public function release(string $schedule, string $occurrence, string $token): void;
}
