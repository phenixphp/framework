<?php

declare(strict_types=1);

namespace Phenix\Events;

use Closure;
use Phenix\Events\Contracts\Event;
use Phenix\Events\Contracts\EventListener as EventListenerContract;

abstract class AbstractListener implements EventListenerContract
{
    protected int $priority = 0;

    protected bool $once = false;

    abstract public function handle(Event $event): mixed;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function shouldHandle(Event $event): bool
    {
        return true;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }

    public function setOnce(bool $once = true): self
    {
        $this->once = $once;

        return $this;
    }

    public function getHandler(): Closure|static|string
    {
        return $this;
    }
}
