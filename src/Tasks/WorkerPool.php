<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker as Workers;
use Phenix\Tasks\Contracts\Task;

class WorkerPool extends AbstractWorker
{
    protected function submitTask(Task $parallelTask): Workers\Execution
    {
        return Workers\submit($parallelTask);
    }
}
