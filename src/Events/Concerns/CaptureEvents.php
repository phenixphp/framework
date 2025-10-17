<?php

declare(strict_types=1);

namespace Phenix\Events\Concerns;

use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event as EventContract;
use Throwable;

trait CaptureEvents
{
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

        $normalized = $this->normalizeFakeEvents($events, $times);

        foreach ($normalized as $name => $config) {
            if ($config === 0) {
                continue;
            }

            $this->fakeEvents[$name] = $config;
        }
    }

    public function getEventLog(): array
    {
        return $this->dispatched;
    }

    public function resetEventLog(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->dispatched = [];
    }

    /**
     * @param string|array $events
     * @param int|Closure|null $times
     * @return array<string, int|Closure|null>
     */
    protected function normalizeFakeEvents(string|array $events, int|Closure|null $times): array
    {
        if (is_string($events)) {
            return $this->normalizeSingleEvent($events, $times);
        }

        if (array_is_list($events)) {
            return $this->normalizeListEvents($events);
        }

        return $this->normalizeMapEvents($events);
    }

    /**
     * @return array<string, int|Closure|null>
     */
    private function normalizeSingleEvent(string $event, int|Closure|null $times): array
    {
        return [
            $event => $times instanceof Closure
                ? $times
                : (is_int($times) ? max(0, abs($times)) : null),
        ];
    }

    /**
     * @param array<int, string> $events
     * @return array<string, null>
     */
    private function normalizeListEvents(array $events): array
    {
        $normalized = [];

        foreach ($events as $event) {
            $normalized[$event] = null;
        }

        return $normalized;
    }

    /**
     * @param array<string|int, mixed> $events
     * @return array<string, int|Closure|null>
     */
    private function normalizeMapEvents(array $events): array
    {
        $normalized = [];

        foreach ($events as $name => $value) {
            if (is_int($name)) {
                $normalized[(string) $value] = null;

                continue;
            }

            if ($value instanceof Closure) {
                $normalized[$name] = $value;

                continue;
            }

            if (is_int($value)) {
                $normalized[$name] = max(0, abs($value));

                continue;
            }

            $normalized[$name] = null;
        }

        return $normalized;
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

    protected function shouldFakeEvent(string $name): bool
    {
        $result = false;

        if (! $this->faking) {
            return $result;
        }

        if ($this->fakeAll) {
            $result = true;
        } elseif (! empty($this->fakeEvents) && array_key_exists($name, $this->fakeEvents)) {
            $config = $this->fakeEvents[$name];

            if ($config instanceof Closure) {
                try {
                    $result = (bool) $config($this->dispatched);
                } catch (Throwable $e) {
                    report($e);

                    $result = false;
                }
            } else {
                $result = $config === null || $config > 0;
            }
        }

        return $result;
    }

    protected function consumeFakedEvent(string $name): void
    {
        if (! isset($this->fakeEvents[$name])) {
            return;
        }

        $remaining = $this->fakeEvents[$name];

        if (! $remaining || $remaining instanceof Closure) {
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
