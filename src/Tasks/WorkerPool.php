<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Future;
use Amp\Parallel\Worker\Execution;
use Amp\TimeoutCancellation;
use Phenix\Facades\Worker;
use Phenix\Tasks\Contracts\Task;

class WorkerPool extends AbstractWorker
{
    public function prepareTask(Task $parallelTask): Execution
    {
        $timeout = new TimeoutCancellation($parallelTask->getTimeout());

        return Worker::submit($parallelTask, $timeout);
    }

    public static function submit(Task $parallelTask): Future
    {
        $timeout = new TimeoutCancellation($parallelTask->getTimeout());

        $execution = Worker::submit($parallelTask, $timeout);

        return $execution->getFuture();
    }
}
