<?php

declare(strict_types=1);

namespace Phenix\Queue;

class WorkerOptions
{
    public function __construct(
        public readonly int $sleep = 3,
        public readonly int $maxTries = 3,
        public readonly int $timeout = 60,
        public readonly bool $once = false,
        public readonly int $retryDelay = 5,
        public readonly int $maxRetryDelay = 300,
        public readonly int $batchSize = 1,
        public readonly bool $processInBatch = false
    ) {
    }

    public function toArray(): array
    {
        return [
            'sleep' => $this->sleep,
            'maxTries' => $this->maxTries,
            'timeout' => $this->timeout,
            'once' => $this->once,
            'retryDelay' => $this->retryDelay,
            'maxRetryDelay' => $this->maxRetryDelay,
            'batchSize' => $this->batchSize,
            'processInBatch' => $this->processInBatch,
        ];
    }
}
