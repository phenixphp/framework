<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Facades\Config;
use Phenix\Queue\Exceptions\FailedTaskException;
use Phenix\Queue\StateManagers\MemoryTaskState;
use Phenix\Tasks\QueuableTask;

use function Amp\async;
use function Amp\delay;

class ParallelQueue extends Queue
{
    public function __construct(
        protected string|null $queueName = 'default'
    ) {
        parent::__construct($queueName);

        $this->stateManager = new MemoryTaskState();
    }

    public function push(QueuableTask $task): void
    {
        parent::push($task);

        $this->runTasks();
    }

    protected function runTasks(): void
    {
        async(function (): void {
            delay(Config::get('queue.drivers.parallel.timeout', 2));

            while ($this->size() > 0) {
                $task = $this->pop();

                if ($task) {
                    $this->runTask($task);
                }
            }
        });
    }

    protected function runTask(QueuableTask $task): void
    {
        $this->stateManager->reserve($task);

        $output = $task->output();

        if ($output->isSuccess()) {
            $this->stateManager->complete($task);

            return;
        }

        $this->stateManager->fail($task, new FailedTaskException($output->message()));
    }
}
