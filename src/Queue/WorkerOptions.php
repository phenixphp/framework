<?php

declare(strict_types=1);

namespace Phenix\Queue;

class WorkerOptions
{
    public function __construct(
        public readonly int $sleep = 3,
        public readonly int $maxTries = 3,
        public readonly bool $once = false,
        public readonly int $retryDelay = 5,
        public readonly int $maxRetryDelay = 300,
        public readonly int $chunkSize = 10,
        public readonly bool $processInChunk = false
    ) {
    }
}
