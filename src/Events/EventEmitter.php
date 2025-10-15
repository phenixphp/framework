<?php

declare(strict_types=1);

namespace Phenix\Events;

use Amp\Future;
use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Events\Contracts\EventEmitter as EventEmitterContract;
use Phenix\Events\Contracts\EventListener as EventListenerContract;
use Phenix\Events\Exceptions\EventException;
use Phenix\Facades\Log;
use Throwable;

use function Amp\async;

class EventEmitter implements EventEmitterContract
{
    /**
     * @var array<string, array<int, EventListenerContract>>
     */
    protected array $listeners = [];

    /**
     * @var array<string, int>
     */
    protected array $listenerCounts = [];

    protected int $maxListeners = 10;

    protected bool $emitWarnings = true;

    protected bool $logging = false;

    protected bool $faking = false;

    protected bool $fakeAll = false;

    /**
     * @var array<string, int|null|Closure>
     */
    protected array $fakeEvents = [];

    /**
     * @var array<int, array{name: string, event: EventContract, payload: mixed, timestamp: float}>
     */
    protected array $dispatched = [];

    public function on(string $event, Closure|EventListenerContract|string $listener, int $priority = 0): void
    {
        $eventListener = $this->createEventListener($listener, $priority);

        $this->listeners[$event][] = $eventListener;
        $this->listenerCounts[$event] = ($this->listenerCounts[$event] ?? 0) + 1;

        $this->sortListenersByPriority($event);
        $this->checkMaxListeners($event);
    }

    public function once(string $event, Closure|EventListenerContract|string $listener, int $priority = 0): void
    {
        $eventListener = $this->createEventListener($listener, $priority);
        $eventListener->setOnce(true);

        $this->listeners[$event][] = $eventListener;
        $this->listenerCounts[$event] = ($this->listenerCounts[$event] ?? 0) + 1;

        $this->sortListenersByPriority($event);
        $this->checkMaxListeners($event);
    }

    public function off(string $event, Closure|EventListenerContract|string|null $listener = null): void
    {
        if (! isset($this->listeners[$event])) {
            return;
        }

        if ($listener === null) {
            unset($this->listeners[$event]);
            $this->listenerCounts[$event] = 0;

            return;
        }

        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            fn (EventListenerContract $eventListener): bool => ! $this->isSameListener($eventListener, $listener)
        );

        $this->listenerCounts[$event] = count($this->listeners[$event]);

        if ($this->listenerCounts[$event] === 0) {
            unset($this->listeners[$event]);
        }
    }

    public function emit(string|EventContract $event, mixed $payload = null): array
    {
        $eventObject = $this->createEvent($event, $payload);

        $this->recordDispatched($eventObject);
        if ($this->shouldFakeEvent($eventObject->getName())) {
            $this->consumeFakedEvent($eventObject->getName());

            return [];
        }

        $results = [];

        $listeners = $this->getListeners($eventObject->getName());

        foreach ($listeners as $listener) {
            if ($eventObject->isPropagationStopped()) {
                break;
            }

            if (! $listener->shouldHandle($eventObject)) {
                continue;
            }

            try {
                $result = $listener->handle($eventObject);
                $results[] = $result;

                // Remove one-time listeners after execution
                if ($listener->isOnce()) {
                    $this->removeListener($eventObject->getName(), $listener);
                }
            } catch (Throwable $e) {
                Log::error('Event listener error', [
                    'event' => $eventObject->getName(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                if ($this->emitWarnings) {
                    throw new EventException(
                        "Error in event listener for '{$eventObject->getName()}': {$e->getMessage()}",
                        0,
                        $e
                    );
                }
            }
        }

        return $results;
    }

    public function emitAsync(string|EventContract $event, mixed $payload = null): Future
    {
        return async(function () use ($event, $payload): array {
            $eventObject = $this->createEvent($event, $payload);

            $this->recordDispatched($eventObject);
            if ($this->shouldFakeEvent($eventObject->getName())) {
                $this->consumeFakedEvent($eventObject->getName());

                return [];
            }

            $listeners = $this->getListeners($eventObject->getName());
            $futures = [];

            foreach ($listeners as $listener) {
                if (! $listener->shouldHandle($eventObject)) {
                    continue;
                }

                $futures[] = $this->handleListenerAsync($listener, $eventObject);
            }

            $results = [];

            foreach ($futures as $future) {
                try {
                    $results[] = $future->await();
                } catch (Throwable $e) {
                    Log::error('Future await error', [
                        'event' => $eventObject->getName(),
                        'error' => $e->getMessage(),
                    ]);

                    $results[] = null;
                }
            }

            return $results;
        });
    }

    public function log(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->logging = true;
    }

    public function fake(string|array|null $events = null, int|Closure|null $times = null): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->logging = true;
        $this->faking = true;

        if ($events === null) {
            $this->fakeAll = true;

            return;
        }

        $this->fakeAll = false;

        $normalized = [];

        if (is_string($events)) {
            if ($times instanceof Closure) {
                $normalized[$events] = $times;
            } elseif (is_int($times)) {
                $normalized[$events] = max(0, abs($times));
            } else {
                $normalized[$events] = null;
            }
        } elseif (is_array($events) && array_is_list($events)) {
            foreach ($events as $event) {
                $normalized[$event] = null;
            }
        } else {
            foreach ($events as $name => $value) {
                if (is_int($name)) {
                    $normalized[(string)$value] = null;

                    continue;
                }

                if (is_int($value)) {
                    $normalized[$name] = max(0, abs($value));
                } elseif ($value instanceof Closure) {
                    $normalized[$name] = $value;
                } else {
                    $normalized[$name] = null;
                }
            }
        }

        foreach ($normalized as $eventName => $count) {
            if ($count === 0) {
                unset($normalized[$eventName]);
            }
        }

        foreach ($normalized as $name => $config) {
            $this->fakeEvents[$name] = $config;
        }
    }

    public function getEventLog(): array
    {
        return $this->dispatched;
    }

    protected function recordDispatched(EventContract $event): void
    {
        if (! $this->logging && ! $this->faking) {
            return;
        }

        $this->dispatched[] = [
            'name' => $event->getName(),
            'event' => $event,
            'payload' => $event->getPayload(),
            'timestamp' => microtime(true),
        ];
    }

    protected function handleListenerAsync(EventListenerContract $listener, EventContract $eventObject): Future
    {
        return async(function () use ($listener, $eventObject): mixed {
            try {
                if ($eventObject->isPropagationStopped()) {
                    return null;
                }

                $result = $listener->handle($eventObject);

                // Remove one-time listeners after execution
                if ($listener->isOnce()) {
                    $this->removeListener($eventObject->getName(), $listener);
                }

                return $result;
            } catch (Throwable $e) {
                Log::error('Async event listener error', [
                    'event' => $eventObject->getName(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                if ($this->emitWarnings) {
                    throw new EventException(
                        "Error in async event listener for '{$eventObject->getName()}': {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                return null;
            }
        });
    }

    /**
     * @return array<int, EventListenerContract>
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }

    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && count($this->listeners[$event]) > 0;
    }

    public function removeAllListeners(): void
    {
        $this->listeners = [];
        $this->listenerCounts = [];
    }

    public function setMaxListeners(int $maxListeners): void
    {
        $this->maxListeners = $maxListeners;
    }

    public function getMaxListeners(): int
    {
        return $this->maxListeners;
    }

    public function setEmitWarnings(bool $emitWarnings): void
    {
        $this->emitWarnings = $emitWarnings;
    }

    public function getListenerCount(string $event): int
    {
        return $this->listenerCounts[$event] ?? 0;
    }

    public function getEventNames(): array
    {
        return array_keys($this->listeners);
    }

    protected function createEventListener(Closure|EventListenerContract|string $listener, int $priority): EventListenerContract
    {
        if ($listener instanceof EventListenerContract) {
            return $listener;
        }

        return new EventListener($listener, $priority);
    }

    protected function createEvent(string|EventContract $event, mixed $payload): EventContract
    {
        if ($event instanceof EventContract) {
            return $event;
        }

        return new Event($event, $payload);
    }

    protected function sortListenersByPriority(string $event): void
    {
        usort($this->listeners[$event], function (EventListenerContract $a, EventListenerContract $b): int {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    protected function checkMaxListeners(string $event): void
    {
        if (! $this->emitWarnings) {
            return;
        }

        $count = $this->getListenerCount($event);

        if ($count > $this->maxListeners) {
            Log::warning("Possible memory leak detected. Event '{$event}' has {$count} listeners. Maximum is {$this->maxListeners}.");
        }
    }

    protected function isSameListener(EventListenerContract $eventListener, Closure|EventListenerContract|string $listener): bool
    {
        $handler = $eventListener->getHandler();

        if ($listener instanceof EventListenerContract) {
            return $eventListener::class === $listener::class;
        }

        return $handler === $listener;
    }

    protected function removeListener(string $event, EventListenerContract $listener): void
    {
        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            fn (EventListenerContract $eventListener): bool => ! $this->isSameListener($eventListener, $listener)
        );

        $this->listenerCounts[$event] = count($this->listeners[$event]);

        if ($this->listenerCounts[$event] === 0) {
            unset($this->listeners[$event]);
        }
    }

    protected function shouldFakeEvent(string $name): bool
    {
        if (! $this->faking) {
            return false;
        }
        if ($this->fakeAll) {
            return true;
        }

        if (empty($this->fakeEvents)) {
            return false;
        }

        if (! array_key_exists($name, $this->fakeEvents)) {
            return false;
        }

        $config = $this->fakeEvents[$name];

        if ($config instanceof Closure) {
            try {
                return (bool) $config($this->dispatched);
            } catch (Throwable $e) {
                report($e);

                return false;
            }
        }

        return $config === null || $config > 0;
    }

    protected function consumeFakedEvent(string $name): void
    {
        if (! isset($this->fakeEvents[$name])) {
            return;
        }

        $remaining = $this->fakeEvents[$name];

        if ($remaining === null || $remaining instanceof Closure) {
            return;
        }

        $remaining--;

        if ($remaining <= 0) {
            unset($this->fakeEvents[$name]);
        } else {
            $this->fakeEvents[$name] = $remaining;
        }
    }
}
