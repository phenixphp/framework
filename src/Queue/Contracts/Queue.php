<?php

declare(strict_types=1);

namespace Phenix\Queue\Contracts;

use Phenix\Tasks\QueuableTask;

interface Queue
{
    public function push(QueuableTask $task): void;

    public function pushOn(string $queue, QueuableTask $task): static;

    public function pop(string|null $queueName = null): QueuableTask|null;

    public function popChunk(int $limit, string|null $queueName = null): array;

    public function size(): int;

    public function clear(): void;

    public function getConnectionName(): string;

    public function setConnectionName(string $name): void;

    public function getStateManager(): TaskState;
}
