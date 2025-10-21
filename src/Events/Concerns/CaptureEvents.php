<?php

declare(strict_types=1);

namespace Phenix\Events\Concerns;

use Closure;
use Phenix\App;
use Phenix\Data\Collection;
use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Testing\Constants\FakeMode;
use Phenix\Util\Date;
use Throwable;

trait CaptureEvents
{
    protected bool $logging = false;

    protected FakeMode $fakeMode = FakeMode::NONE;

    /**
     * @var array<string, int|null|Closure>
     */
    protected array $fakeEvents = [];

    /**
     * @var Collection<int, array{name: string, event: EventContract, timestamp: float}>
     */
    protected Collection $dispatched;

    public function log(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableLog();
    }

    public function fake(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::ALL);
    }

    public function fakeWhen(string $event, Closure $callback): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeEvents[$event] = $callback;
    }

    public function fakeTimes(string $event, int $times): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeEvents[$event] = $times;
    }

    public function fakeOnce(string $event): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeEvents[$event] = 1;
    }

    public function fakeOnly(string $event): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeEvents = [
            $event => null,
        ];
    }

    public function fakeExcept(string $event): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeEvents = [
            $event => fn (Collection $log): bool => $log->filter(fn (array $entry): bool => $entry['name'] === $event)->isEmpty(),
        ];
    }

    public function getEventLog(): Collection
    {
        if (! isset($this->dispatched)) {
            $this->dispatched = Collection::fromArray([]);
        }

        return $this->dispatched;
    }

    public function resetEventLog(): void
    {
        $this->dispatched = Collection::fromArray([]);
    }

    public function resetFaking(): void
    {
        $this->logging = false;
        $this->fakeMode = FakeMode::NONE;
        $this->fakeEvents = [];
        $this->dispatched = Collection::fromArray([]);
    }

    protected function recordDispatched(EventContract $event): void
    {
        if (! $this->logging) {
            return;
        }

        $this->dispatched->add([
            'name' => $event->getName(),
            'event' => $event,
            'timestamp' => Date::now(),
        ]);
    }

    protected function shouldFakeEvent(string $name): bool
    {
        if ($this->fakeMode === FakeMode::ALL) {
            return true;
        }

        $result = false;

        if (! empty($this->fakeEvents) && array_key_exists($name, $this->fakeEvents)) {
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

    protected function enableLog(): void
    {
        if (! $this->logging) {
            $this->logging = true;
            $this->dispatched = Collection::fromArray([]);
        }
    }

    protected function enableFake(FakeMode $fakeMode): void
    {
        $this->enableLog();
        $this->fakeMode = $fakeMode;
    }
}
