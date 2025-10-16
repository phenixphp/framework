<?php

declare(strict_types=1);

namespace Phenix\Events\Concerns;

use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event as EventContract;

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
            if ($times instanceof Closure) {
                return [$events => $times];
            }

            if (is_int($times)) {
                return [$events => max(0, abs($times))];
            }

            return [$events => null];
        }

        $normalized = [];

        if (array_is_list($events)) {
            foreach ($events as $event) {
                $normalized[$event] = null;
            }

            return $normalized;
        }

        foreach ($events as $name => $value) {
            if (is_int($name)) {
                $normalized[(string) $value] = null;

                continue;
            }

            if (is_int($value)) {
                $normalized[$name] = max(0, abs($value));

                continue;
            }

            if ($value instanceof Closure) {
                $normalized[$name] = $value;

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
