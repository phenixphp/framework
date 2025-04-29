<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

use Phenix\Tasks\AppParallelTask;

interface Worker
{
    public function submit(AppParallelTask $parallelTask): self;

    public function run(): array;

    /**
     * @param AppParallelTask[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array;
}
