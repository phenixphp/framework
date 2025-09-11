<?php

declare(strict_types=1);

namespace Phenix\Events;

use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event;

class EventListener extends AbstractListener
{
    protected bool $once = false;

    public function __construct(
        protected Closure|string $handler,
        int $priority = 0
    ) {
        $this->priority = $priority;
    }

    public function handle(Event $event): mixed
    {
        if ($this->handler instanceof Closure) {
            return ($this->handler)($event);
        }

        // Handle string-based class listeners
        if (is_string($this->handler)) {
            $listener = App::make($this->handler);

            if (method_exists($listener, 'handle')) {
                return $listener->handle($event);
            }

            if (is_callable($listener)) {
                return $listener($event);
            }
        }

        return null;
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

    public function getHandler(): Closure|string
    {
        return $this->handler;
    }
}
