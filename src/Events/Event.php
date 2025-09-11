<?php

declare(strict_types=1);

namespace Phenix\Events;

class Event extends AbstractEvent
{
    public function __construct(
        protected string $name,
        protected mixed $payload = null
    ) {
        $this->timestamp = microtime(true);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
