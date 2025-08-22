<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

interface Worker
{
    public function submit(Task $parallelTask): self;

    public function run(): array;

    /**
     * @param Task[] $tasks
     * @return array
     */
    public static function batch(array $tasks): array;
}
