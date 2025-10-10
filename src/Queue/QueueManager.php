<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\App;
use Phenix\Database\Constants\Driver as DatabaseDriver;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\Contracts\Queue;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;

class QueueManager
{
    protected array $drivers = [];

    protected Config $config;


    protected bool $logging = false;

    protected bool $faking = false;

    protected bool $hasFakeTasks = false;

    /**
     * @var array<string,bool>
     */
    protected array $fakeTasks = [];

    /**
     * @var array<int, array{task_class: class-string<\Phenix\Tasks\QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}>
     */
    protected array $pushed = [];

    public function __construct(Config|null $config = null)
    {
        $this->config = $config ?? new Config();
    }

    public function push(QueuableTask $task): void
    {
        $this->recordPush($task);

        if ($this->shouldFakeTask($task)) {
            $this->consumeFakedTask($task);

            return;
        }

        $this->driver()->push($task);
    }

    public function pushOn(string $queueName, QueuableTask $task): void
    {
        $task->setQueueName($queueName);
        $this->recordPush($task);

        if ($this->shouldFakeTask($task)) {
            $this->consumeFakedTask($task);

            return;
        }

        $this->driver()->pushOn($queueName, $task);
    }

    public function pop(string|null $queueName = null): QueuableTask|null
    {
        return $this->driver()->pop($queueName);
    }

    public function size(): int
    {
        return $this->driver()->size();
    }

    /**
     * @return array<int, QueuableTask>
     */
    public function popChunk(int $limit, string|null $queueName = null): array
    {
        return $this->driver()->popChunk($limit, $queueName);
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

    public function driver(QueueDriver|null $driverName = null): Queue
    {
        $driverName = $this->resolveDriverName($driverName);

        return $this->drivers[$driverName->value] ??= $this->resolveDriver($driverName);
    }

    public function log(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->logging = true;
    }

    public function fake(string|array|null $tasks = null): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->logging = true;
        $this->faking = true;
        $this->hasFakeTasks = $tasks !== null;

        if ($tasks !== null) {
            foreach ((array) $tasks as $name) {
                $this->fakeTasks[$name] = true;
            }
        }
    }

    /**
     * @return array<int, array{task_class: class-string<\Phenix\Tasks\QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}>
     */
    public function getQueueLog(): array
    {
        return $this->pushed;
    }

    protected function recordPush(QueuableTask $task): void
    {
        if (! $this->logging && ! $this->faking) {
            return;
        }

        $this->pushed[] = [
            'task_class' => $task::class,
            'task' => $task,
            'queue' => $task->getQueueName(),
            'connection' => $task->getConnectionName(),
            'timestamp' => microtime(true),
        ];
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
        };
    }

    protected function shouldFakeTask(QueuableTask $task): bool
    {
        if (! $this->faking) {
            return false;
        }

        if ($this->hasFakeTasks) {
            return isset($this->fakeTasks[$task::class]);
        }

        return true;
    }

    protected function consumeFakedTask(QueuableTask $task): void
    {
        if (isset($this->fakeTasks[$task::class])) {
            unset($this->fakeTasks[$task::class]);
        }
    }

    protected function createParallelDriver(): Queue
    {
        return new ParallelQueue();
    }

    protected function createDatabaseDriver(): Queue
    {
        $config = $this->config->getDriver(QueueDriver::DATABASE->value);

        return new DatabaseQueue(
            connection: $config['connection'] ?? DatabaseDriver::MYSQL->value,
            queueName: $config['queue'] ?? 'default',
            table: $config['table'] ?? 'tasks'
        );
    }

    protected function createRedisDriver(): Queue
    {
        $config = $this->config->getDriver(QueueDriver::REDIS->value);

        return new RedisQueue(
            redis: App::make(Client::class),
            queueName: $config['queue'] ?? 'default'
        );
    }
}
