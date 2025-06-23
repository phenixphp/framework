<?php

declare(strict_types=1);

namespace Phenix\Queue;

use InvalidArgumentException;
use Phenix\Tasks\QueuableTask;
use Phenix\Queue\Contracts\Queue;
use Phenix\Queue\Constants\QueueDriver;

class QueueManager
{
    protected array $drivers = [];

    protected Config $config;

    public function __construct(Config|null $config = null)
    {
        $this->config = $config ?? new Config();
    }

    public function push(QueuableTask $task): void
    {
        $this->driver()->push($task);
    }

    public function pushOn(string $queue, QueuableTask $task): void
    {
        $this->driver()->pushOn($queue, $task);
    }

    public function pop(): QueuableTask|null
    {
        return $this->driver()->pop();
    }

    public function size(): int
    {
        return $this->driver()->size();
    }

    public function clear(): void
    {
        $this->driver()->clear();
    }

    public function getConnectionName(): string
    {
        return $this->driver()->getConnectionName();
    }

    public function setConnectionName(string $name): void
    {
        $this->driver()->setConnectionName($name);
    }

    protected function driver(QueueDriver|null $driverName = null): Queue
    {
        $driverName = $this->resolveDriverName($driverName);

        return $this->drivers[$driverName->value] ??= $this->resolveDriver($driverName);
    }

    protected function resolveDriverName(QueueDriver|null $driverName = null): QueueDriver
    {
        return $driverName ?? QueueDriver::from($this->config->default());
    }

    protected function resolveDriver(QueueDriver $driverName): Queue
    {
        return match ($driverName) {
            QueueDriver::PARALLEL => $this->createParallelDriver(),
            QueueDriver::DATABASE => $this->createDatabaseDriver(),
            QueueDriver::REDIS => $this->createRedisDriver(),
            default => throw new InvalidArgumentException("Unsupported queue driver: {$driverName->value}"),
        };
    }

    protected function createParallelDriver(): Queue
    {
        return new ParallelQueue();
    }

    protected function createDatabaseDriver(): Queue
    {
        $config = $this->config->getDriver(QueueDriver::DATABASE->value);

        return new DatabaseQueue(
            connection: $config['connection'] ?? 'default',
            queueName: $config['queue'] ?? 'default',
            table: $config['table'] ?? 'tasks'
        );
    }

    protected function createRedisDriver(): Queue
    {
        $config = $this->config->getDriver(QueueDriver::REDIS->value);

        return new RedisQueue(
            connection: $config['connection'] ?? 'default',
            queueName: $config['queue'] ?? 'default'
        );
    }
}
