<?php

declare(strict_types=1);

namespace Phenix\Events;

class Event extends AbstractEvent
{
    protected float $timestamp;

    public function __construct(
        protected string $name,
        mixed $payload = null
    ) {
        $this->payload = $payload;
        $this->timestamp = microtime(true);
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
