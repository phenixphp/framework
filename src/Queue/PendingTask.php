<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Facades\Queue;
use Phenix\Tasks\QueuableTask;

class PendingTask
{
    public function __construct(
        protected QueuableTask $task
    ) {
    }

    public function __destruct()
    {
        Queue::push($this->task);
    }

    public function onConnection(string $connection): static
    {
        $this->task->setConnectionName($connection);

        return $this;
    }

    public function onQueue(string $queue): static
    {
        $this->task->setQueueName($queue);

        return $this;
    }
}
