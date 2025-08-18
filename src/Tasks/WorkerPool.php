<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker;
use Amp\Parallel\Worker\WorkerPool as Pool;
use Amp\TimeoutCancellation;
use Phenix\App;
use Phenix\Tasks\Contracts\Task;

class WorkerPool extends AbstractWorker
{
    protected function submitTask(Task $parallelTask): Worker\Execution
    {
        /** @var Pool $pool */
        $pool = App::make(Pool::class);

        $timeout = new TimeoutCancellation($parallelTask->getTimeout());

        return $pool->submit($parallelTask, $timeout);
    }
}
