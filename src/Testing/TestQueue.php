<?php

declare(strict_types=1);

namespace Phenix\Testing;

use Closure;
use Phenix\Data\Collection;
use Phenix\Tasks\QueuableTask;
use PHPUnit\Framework\Assert;

class TestQueue
{
    /**
     * @param class-string<QueuableTask> $taskClass
     * @param Collection<int, array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}> $log
     */
    public function __construct(
        protected string $taskClass,
        public readonly Collection $log
    ) {
    }

    public function toBePushed(Closure|null $closure = null): void
    {
        $matches = $this->filterByTaskClass($this->taskClass);

        if ($closure) {
            /** @var QueuableTask|null $task */
            $task = $matches->first()['task'] ?? null;

            Assert::assertTrue($closure($task), "Failed asserting that task '{$this->taskClass}' was pushed with given conditions.");
        } else {
            Assert::assertNotEmpty($matches, "Failed asserting that task '{$this->taskClass}' was pushed at least once.");
        }
    }

    public function toNotBePushed(Closure|null $closure = null): void
    {
        $matches = $this->filterByTaskClass($this->taskClass);

        if ($closure) {
            /** @var QueuableTask|null $task */
            $task = $matches->first()['task'] ?? null;

            Assert::assertFalse($closure($task), "Failed asserting that task '{$this->taskClass}' was NOT pushed with given conditions.");
        } else {
            Assert::assertEmpty($matches, "Failed asserting that task '{$this->taskClass}' was NOT pushed.");
        }
    }

    public function toBePushedTimes(int $times): void
    {
        $matches = $this->filterByTaskClass($this->taskClass);

        Assert::assertCount(
            $times,
            $matches,
            "Failed asserting that task '{$this->taskClass}' was pushed {$times} times. Actual: {$matches->count()}."
        );
    }

    public function toPushNothing(): void
    {
        Assert::assertEmpty($this->log, 'Failed asserting that no tasks were pushed.');
    }

    private function filterByTaskClass(string $taskClass): Collection
    {
        /** @var Collection<int, array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}> $filtered */
        $filtered = $this->log->filter(fn (array $record) => $record['task_class'] === $taskClass);

        return $filtered;
    }
}
