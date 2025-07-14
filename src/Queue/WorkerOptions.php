<?php

declare(strict_types=1);

namespace Phenix\Queue;

class WorkerOptions
{
    public function __construct(
        public readonly int $sleep
    ) {}
}
