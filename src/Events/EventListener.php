<?php

declare(strict_types=1);

namespace Phenix\Events;

use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event;
use Phenix\Events\Contracts\EventListener as EventListenerContract;
use Phenix\Events\Exceptions\EventListenerException;

use function class_exists;
use function is_callable;

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

        $listener = null;

        if (App::has($this->handler)) {
            $listener = App::make($this->handler);
        } elseif (class_exists($this->handler)) {
            $listener = new $this->handler();
        }

        if (! $listener || ! $listener instanceof EventListenerContract && ! is_callable($listener)) {
            throw new EventListenerException("Resolved listener is invalid: {$this->handler}");
        }

        return $listener instanceof EventListenerContract ? $listener->handle($event) : $listener($event);
    }

    public function getHandler(): Closure|string
    {
        return $this->handler;
    }
}
