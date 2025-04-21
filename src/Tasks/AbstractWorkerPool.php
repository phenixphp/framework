<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker as Workers;
use Phenix\Tasks\Contracts\WorkerPoolContract;

abstract class AbstractWorkerPool implements WorkerPoolContract
{
    /**
     * @var Workers\Execution[]
     */
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
    }

    public function push(ParallelTask $parallelTask): self
    {
        $this->tasks[] = $this->submitTask($parallelTask);

        return $this;
    }

    public function run(): array
    {
        return Future\await(array_map(
            fn (Workers\Execution $e) => $e->getFuture(),
            $this->tasks,
        ));
    }

    /**
     * @param ParallelTask[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array
    {
        $pool = new static();

        foreach ($tasks as $task) {
            $pool->push($task);
        }

        $results = $pool->run();

        $pool->finalize();

        return $results;
    }

    abstract protected function submitTask(ParallelTask $parallelTask): Workers\Execution;

    protected function finalize(): void
    {
        // Optional: Override in subclasses if needed
    }
}