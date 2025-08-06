<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker;
use Phenix\Tasks\Contracts\Task;

class WorkerPool extends AbstractWorker
{
    protected function submitTask(Task $parallelTask): Worker\Execution
    {
        return Worker\submit($parallelTask);
    }
}
