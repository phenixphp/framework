<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use Phenix\Events\Contracts\Event as EventContract;

class TestEvents
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
            expect($closure($matches->first()['event'] ?? null))->toBeTrue();
        } else {
            expect($matches)->not->toBeEmpty();
        }
    }

    public function toNotBeDispatched(Closure|null $closure = null): void
    {
        $matches = $this->filterByName($this->event);

        if ($closure) {
            expect($closure($matches->first()['event'] ?? null))->toBeFalse();
        } else {
            expect($matches)->toBeEmpty();
        }
    }

    public function toBeDispatchedTimes(int $times): void
    {
        $matches = $this->filterByName($this->event);

        expect($matches)->toHaveCount($times);
    }

    public function toDispatchNothing(): void
    {
        expect($this->log)->toBeEmpty();
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
