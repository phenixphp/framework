<?php

declare(strict_types=1);

namespace Phenix\Queue\Contracts;

use Phenix\Tasks\QueuableTask;

interface Queue
{
    public function push(QueuableTask $job): void;

    public function pushOn(string $queue, QueuableTask $job): static;

    public function pop(): QueuableTask|null;

    public function size(): int;

    public function clear(): void;

    public function getConnectionName(): string;

    public function setConnectionName(string $name): void;
}
