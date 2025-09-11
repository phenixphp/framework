<?php

declare(strict_types=1);

namespace Phenix\Events;

use Phenix\Events\Contracts\Event as EventContract;

abstract class AbstractEvent implements EventContract
{
    protected bool $propagationStopped = false;

    protected float $timestamp;

    public function __construct(
        protected mixed $payload = null
    ) {
        $this->timestamp = microtime(true);
    }

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

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
