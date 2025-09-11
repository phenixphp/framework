<?php

declare(strict_types=1);

namespace Phenix\Events;

use Phenix\Events\Contracts\Event;
use Phenix\Events\Contracts\EventListener as EventListenerContract;

abstract class AbstractListener implements EventListenerContract
{
    protected int $priority = 0;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function shouldHandle(Event $event): bool
    {
        return true;
    }

    abstract public function handle(Event $event): mixed;
}
