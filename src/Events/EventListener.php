<?php

declare(strict_types=1);

namespace Phenix\Events;

use Closure;
use Phenix\Events\Contracts\Event;

use function class_exists;
use function is_callable;
use function method_exists;

class EventListener extends AbstractListener
{
    public function __construct(
        protected Closure|string $handler,
        int $priority = 0
    ) {
        $this->priority = $this->normalizePriority($priority);
    }

    public function handle(Event $event): mixed
    {
        if ($this->handler instanceof Closure) {
            return ($this->handler)($event);
        }

        $listener = $this->resolveListener();

        if (! $listener || ! (method_exists($listener, 'handle') || is_callable($listener))) {
            return null;
        }

        return method_exists($listener, 'handle') ? $listener->handle($event) : $listener($event);
    }

    public function getHandler(): Closure|string
    {
        return $this->handler;
    }

    private function resolveListener(): object|null
    {
        return class_exists($this->handler)
            ? new $this->handler()
            : null;
    }
}
