<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker as Workers;
use Phenix\Tasks\Contracts\Worker as WorkerContract;

/** @phpstan-consistent-constructor */
abstract class AbstractWorker implements WorkerContract
{
    /**
     * @var Workers\Execution[]
     */
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
    }

    public function submit(AppParallelTask $parallelTask): self
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
     * @param AppParallelTask[] $tasks
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

    abstract protected function submitTask(AppParallelTask $parallelTask): Workers\Execution;

    protected function finalize(): void
    {
        // Optional: Override in subclasses if needed
    }
}
