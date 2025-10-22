<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use PHPUnit\Framework\Assert;

class TestEvent
{
    public function __construct(
        public readonly string $event,
        public readonly Collection $log
    ) {
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
        /** @var Collection<int, array{name: string, event: object, timestamp: float}> $filtered */
        $filtered = $this->log->filter(fn (array $record) => $record['name'] === $event);

        return $filtered;
    }
}
