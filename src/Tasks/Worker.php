<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker as Workers;
use Amp\Parallel\Worker\Worker as WorkerContract;

class Worker extends AbstractWorker
{
    protected WorkerContract $worker;

    public function __construct()
    {
        parent::__construct();
        $this->worker = Workers\createWorker();
    }

    protected function submitTask(AppParallelTask $parallelTask): Workers\Execution
    {
        return $this->worker->submit($parallelTask);
    }

    protected function finalize(): void
    {
        $this->worker->shutdown();
    }
}
