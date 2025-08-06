<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker;
use Phenix\Tasks\Contracts\Task;
use Phenix\Tasks\Contracts\Worker as WorkerContract;

/** @phpstan-consistent-constructor */
abstract class AbstractWorker implements WorkerContract
{
    /**
     * @var Worker\Execution[]
     */
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
    }

    /**
     * @param Task[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array
    {
        $pool = new static();

        foreach ($tasks as $task) {
            $pool->submit($task);
        }

        $results = $pool->run();

        $pool->finalize();

        return $results;
    }

    public function submit(Task $parallelTask): self
    {
        $this->tasks[] = $this->submitTask($parallelTask);

        return $this;
    }

    public function run(): array
    {
        return Future\await(array_map(
            fn (Worker\Execution $e): Future => $e->getFuture(),
            $this->tasks,
        ));
    }

    abstract protected function submitTask(Task $parallelTask): Worker\Execution;

    protected function finalize(): void
    {
        // Optional: Override in subclasses if needed
    }
}
