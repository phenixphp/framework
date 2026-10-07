<?php

declare(strict_types=1);

namespace Tests\Internal;

use Phenix\Scheduling\Contracts\ScheduleLock;

class FakeScheduleLock implements ScheduleLock
{
    /** @var array<string, string> */
    protected array $locks = [];

    protected int $sequence = 0;

    public function acquire(string $schedule, string $occurrence, int $ttl): string|null
    {
        $key = "{$schedule}:{$occurrence}";

        if (isset($this->locks[$key])) {
            return null;
        }

        $token = 'token-' . ++$this->sequence;
        $this->locks[$key] = $token;

        return $token;
    }

    public function release(string $schedule, string $occurrence, string $token): void
    {
        $key = "{$schedule}:{$occurrence}";

        if (($this->locks[$key] ?? null) === $token) {
            unset($this->locks[$key]);
        }
    }
}
