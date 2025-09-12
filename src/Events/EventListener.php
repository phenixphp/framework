<?php

declare(strict_types=1);

namespace Phenix\Events;

use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event;

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
        $result = null;

        if ($this->handler instanceof Closure) {
            $result = ($this->handler)($event);
        } elseif (is_string($this->handler)) {
            $listener = App::make($this->handler);

            if (method_exists($listener, 'handle')) {
                $result = $listener->handle($event);
            } elseif (is_callable($listener)) {
                $result = $listener($event);
            }
        }

        return $result;
    }

    public function getHandler(): Closure|string
    {
        return $this->handler;
    }
}
