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

    public function lockForNoKeyUpdate(): static
    {
        return $this->lock(Lock::FOR_NO_KEY_UPDATE);
    }

    public function lockForKeyShare(): static
    {
        return $this->lock(Lock::FOR_KEY_SHARE);
    }

    public function lockForShareNoWait(): static
    {
        return $this->lock(Lock::FOR_SHARE_NOWAIT);
    }

    public function lockForShareSkipLocked(): static
    {
        return $this->lock(Lock::FOR_SHARE_SKIP_LOCKED);
    }

    public function lockForNoKeyUpdateNoWait(): static
    {
        return $this->lock(Lock::FOR_NO_KEY_UPDATE_NOWAIT);
    }

    public function lockForNoKeyUpdateSkipLocked(): static
    {
        return $this->lock(Lock::FOR_NO_KEY_UPDATE_SKIP_LOCKED);
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
        if ($this->driver === Driver::POSTGRESQL) {
            return match ($this->lockType) {
                Lock::FOR_UPDATE => 'FOR UPDATE',
                Lock::FOR_SHARE => 'FOR SHARE',
                Lock::FOR_NO_KEY_UPDATE => 'FOR NO KEY UPDATE',
                Lock::FOR_KEY_SHARE => 'FOR KEY SHARE',
                Lock::FOR_UPDATE_SKIP_LOCKED => 'FOR UPDATE SKIP LOCKED',
                Lock::FOR_SHARE_SKIP_LOCKED => 'FOR SHARE SKIP LOCKED',
                Lock::FOR_NO_KEY_UPDATE_SKIP_LOCKED => 'FOR NO KEY UPDATE SKIP LOCKED',
                Lock::FOR_UPDATE_NOWAIT => 'FOR UPDATE NOWAIT',
                Lock::FOR_SHARE_NOWAIT => 'FOR SHARE NOWAIT',
                Lock::FOR_NO_KEY_UPDATE_NOWAIT => 'FOR NO KEY UPDATE NOWAIT',
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
                [
                    Lock::FOR_UPDATE,
                    Lock::FOR_SHARE,
                    Lock::FOR_NO_KEY_UPDATE,
                    Lock::FOR_KEY_SHARE,
                    Lock::FOR_UPDATE_SKIP_LOCKED,
                    Lock::FOR_SHARE_SKIP_LOCKED,
                    Lock::FOR_NO_KEY_UPDATE_SKIP_LOCKED,
                    Lock::FOR_UPDATE_NOWAIT,
                    Lock::FOR_SHARE_NOWAIT,
                    Lock::FOR_NO_KEY_UPDATE_NOWAIT,
                ],
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
