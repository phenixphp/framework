<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker as Workers;
use Amp\Parallel\Worker\Worker as WorkerContract;

class Worker
{
    protected WorkerContract $worker;

    /**
     * @var Workers\Execution[]
     */
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
        $this->worker = Workers\createWorker();
    }

    public function push(ParallelTask $parallelTask): self
    {
        $this->tasks[] = $this->worker->submit($parallelTask);

        return $this;
    }

    public function run(): array
    {
        return Future\await(array_map(
            fn (Workers\Execution $e) => $e->getFuture(),
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
    public static function batch(array $tasks): array
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
