<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker;

class TaskPool
{
    protected array $tasks;

    public function __construct()
    {
        $this->tasks = [];
    }

    public function push(ParallelTask $parallelTask): self
    {
        $this->tasks[] = Worker\submit($parallelTask);

        return $this;
    }

    public function run(): array
    {
        return Future\await(array_map(
            fn (Worker\Execution $e) => $e->getFuture(),
            $this->tasks,
        ));
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

        return $pool->run();
    }
}
