<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker;
use Amp\TimeoutCancellation;
use Phenix\Tasks\Contracts\Task;

class WorkerPool extends AbstractWorker
{
    protected function submitTask(Task $parallelTask): Worker\Execution
    {
        $timeout = new TimeoutCancellation($parallelTask->getTimeout());

        return Worker\submit($parallelTask, $timeout);
    }
}
