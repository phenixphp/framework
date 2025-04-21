<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

use Phenix\Tasks\ParallelTask;

interface Worker
{
    public function push(ParallelTask $parallelTask): self;

    public function run(): array;

    /**
     * @param ParallelTask[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array;
}