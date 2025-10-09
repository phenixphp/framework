<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use Phenix\Events\Contracts\Event as EventContract;
use PHPUnit\Framework\Assert;

class TestEvent
{
    public readonly Collection $log;

    /**
     * @param array<int, array{name: string, event: EventContract, payload: mixed, timestamp: float}> $log
     */
    public function __construct(
        protected string $event,
        array $log = []
    ) {
        $this->log = Collection::fromArray($log);
    }

    public function toBeDispatched(Closure|null $closure = null): void
    {
        $matches = $this->filterByName($this->event);

        if ($closure) {
            Assert::assertTrue($closure($matches->first()['event'] ?? null));
        } else {
            Assert::assertNotEmpty($matches, "Failed asserting that event '{$this->event}' was dispatched at least once.");
        }
    }

    public function toNotBeDispatched(Closure|null $closure = null): void
    {
        $matches = $this->filterByName($this->event);

        if ($closure) {
            Assert::assertFalse($closure($matches->first()['event'] ?? null));
        } else {
            Assert::assertEmpty($matches, "Failed asserting that event '{$this->event}' was NOT dispatched.");
        }
    }

    public function toBeDispatchedTimes(int $times): void
    {
        $matches = $this->filterByName($this->event);

        Assert::assertCount($times, $matches, "Failed asserting that event '{$this->event}' was dispatched {$times} times. Actual: {$matches->count()}.");
    }

    public function toDispatchNothing(): void
    {
        Assert::assertEmpty($this->log, "Failed asserting that no events were dispatched.");
    }

    private function filterByName(string $event): Collection
    {
        $filtered = [];

        foreach ($this->log as $record) {
            if ($record['name'] === $event) {
                $filtered[] = $record;
            }
        }

        return Collection::fromArray($filtered);
    }
}
