<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\StateManagers\MemoryTaskState;

class ParallelQueue extends Queue
{
    public function __construct(
        protected string|null $queueName = 'default'
    ) {
        parent::__construct($queueName);

        $this->stateManager = new MemoryTaskState();
    }
}
