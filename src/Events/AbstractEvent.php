<?php

declare(strict_types=1);

namespace Phenix\Events;

use Phenix\Events\Contracts\Event as EventContract;

abstract class AbstractEvent implements EventContract
{
    protected mixed $payload = null;

    protected bool $propagationStopped = false;

    public function getName(): string
    {
        return static::class;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
