<?php

declare(strict_types=1);

namespace Phenix\Events\Concerns;

use Closure;
use Throwable;
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
        $normalized = [];

        if (is_string($events)) {
            $normalized[$events] = $times instanceof Closure
                ? $times
                : (is_int($times) ? max(0, abs($times)) : null);
        } elseif (array_is_list($events)) {
            foreach ($events as $event) {
                $normalized[$event] = null;
            }
        } else {
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

        if (!$this->faking) {
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
