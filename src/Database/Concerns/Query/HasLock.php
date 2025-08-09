<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Lock;

use function in_array;

trait HasLock
{
    protected bool $isLocked = false;

    protected Lock|null $lockType = null;

    public function lock(Lock $lockType): static
    {
        if (! $this->supportsLock($lockType)) {
            $this->isLocked = false;
            $this->lockType = null;

            return $this;
        }

        $this->isLocked = true;
        $this->lockType = $lockType;

        return $this;
    }

    public function lockForUpdate(): static
    {
        return $this->lock(Lock::FOR_UPDATE);
    }

    public function lockForShare(): static
    {
        return $this->lock(Lock::FOR_SHARE);
    }

    public function lockForUpdateSkipLocked(): static
    {
        return $this->lock(Lock::FOR_UPDATE_SKIP_LOCKED);
    }

    public function lockForUpdateNoWait(): static
    {
        return $this->lock(Lock::FOR_UPDATE_NOWAIT);
    }

    public function unlock(): static
    {
        $this->isLocked = false;
        $this->lockType = null;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    protected function buildLock(): string
    {
        if ($this->driver === Driver::SQLITE) {
            return '';
        }

        if ($this->driver === Driver::POSTGRESQL) {
            return match ($this->lockType) {
                Lock::FOR_UPDATE => 'FOR UPDATE',
                Lock::FOR_SHARE => 'FOR SHARE',
                Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
                Lock::FOR_UPDATE_NOWAIT => 'FOR UPDATE NOWAIT',
                default => '',
            };
        }

        return match ($this->lockType) {
            Lock::FOR_UPDATE => 'FOR UPDATE',
            Lock::FOR_SHARE => 'FOR SHARE',
            Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
            default => '',
        };
    }

    private function supportsLock(Lock $type): bool
    {
        return match ($this->driver) {
            Driver::POSTGRESQL => in_array(
                $type,
                [Lock::FOR_UPDATE, Lock::FOR_SHARE, Lock::FOR_UPDATE_SKIP_LOCKED, Lock::FOR_UPDATE_NOWAIT],
                true
            ),
            Driver::MYSQL => in_array(
                $type,
                [Lock::FOR_UPDATE, Lock::FOR_SHARE, Lock::FOR_UPDATE_SKIP_LOCKED],
                true
            ),
            Driver::SQLITE => false,
            default => false,
        };
    }
}
