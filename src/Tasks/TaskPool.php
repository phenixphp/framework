<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker;
use Amp\Parallel\Worker\Worker as WorkerContract;

class TaskPool
{
    protected WorkerContract $worker;

    /**
     * @var Worker\Execution[]
     */
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
        $this->worker = Worker\createWorker();
    }

    public function push(ParallelTask $parallelTask): self
    {
        $this->tasks[] = $this->worker->submit($parallelTask);

        return $this;
    }

    public function run(): array
    {
        return Future\await(array_map(
            fn (Worker\Execution $e) => $e->getFuture(),
            $this->tasks,
        ));
    }

    public function shutdown(): void
    {
        $this->worker->shutdown();
    }

    /**
     * @param ParallelTask[] $tasks
     * @return array
     */
    public static function pool(array $tasks): array
    {
        $pool = new self();

        foreach ($tasks as $task) {
            $pool->push($task);
        }

        $results = $pool->run();

        $pool->shutdown();

        return $results;
    }
}
