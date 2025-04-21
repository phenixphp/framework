<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

use Amp\Parallel\Worker as Workers;
use Phenix\Tasks\ParallelTask;

interface WorkerPoolContract
{
    public function push(ParallelTask $parallelTask): self;

    public function run(): array;

    /**
     * @param ParallelTask[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array;

    public function finalize(): void;

    public function submitTask(ParallelTask $parallelTask): Workers\Execution;
}