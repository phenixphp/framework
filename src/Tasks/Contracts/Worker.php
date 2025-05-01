<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

interface Worker
{
    public function submit(ParallelTask $parallelTask): self;

    public function run(): array;

    /**
     * @param ParallelTask[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array;
}
