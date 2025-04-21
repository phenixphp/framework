<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker as Workers;

class WorkerPool extends AbstractWorkerPool
{
    protected function submitTask(ParallelTask $parallelTask): Workers\Execution
    {
        return Workers\submit($parallelTask);
    }
}