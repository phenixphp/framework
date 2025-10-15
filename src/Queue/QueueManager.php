<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Closure;
use Phenix\App;
use Phenix\Database\Constants\Driver as DatabaseDriver;
use Phenix\Queue\Constants\QueueDriver;
use Phenix\Queue\Contracts\Queue;
use Phenix\Redis\Contracts\Client;
use Phenix\Tasks\QueuableTask;
use Throwable;

class QueueManager
{
    protected array $drivers = [];

    protected Config $config;


    protected bool $logging = false;

    protected bool $faking = false;

    protected bool $fakeAll = false;

    /**
     * @var array<string, int|null|Closure>
     */
    protected array $fakeTasks = [];

    /**
     * @var array<int, array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}>
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

    /**
     * @param string|array<class-string<QueuableTask>, int|Closure|null>|class-string<QueuableTask>|null $tasks
     * @param int|Closure|null $times
     */
    public function fake(string|array|null $tasks = null, int|Closure|null $times = null): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->logging = true;
        $this->faking = true;
        $this->fakeAll = $tasks === null;

        if ($this->fakeAll) {
            return;
        }

        $normalized = $this->normalizeFakeTasks($tasks, $times);

        foreach ($normalized as $taskClass => $config) {
            if ($config === 0) {
                continue;
            }

            $this->fakeTasks[$taskClass] = $config;
        }
    }

    /**
     * @return array<int, array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}>
     */
    public function getQueueLog(): array
    {
        return $this->pushed;
    }

    public function resetQueueLog(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->pushed = [];
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

        if ($this->fakeAll) {
            return true;
        }

        if (empty($this->fakeTasks)) {
            return false;
        }

        $class = $task::class;

        if (! array_key_exists($class, $this->fakeTasks)) {
            return false;
        }

        $config = $this->fakeTasks[$class];

        if ($config instanceof Closure) {
            try {
                return (bool) $config($this->pushed);
            } catch (Throwable $e) {
                report($e);

                return false;
            }
        }

        return $config === null || $config > 0;
    }

    protected function consumeFakedTask(QueuableTask $task): void
    {
        $class = $task::class;

        if (! array_key_exists($class, $this->fakeTasks)) {
            return;
        }

        $remaining = $this->fakeTasks[$class];

        if ($remaining === null || $remaining instanceof Closure) {
            return;
        }

        $remaining--;
        if ($remaining <= 0) {
            unset($this->fakeTasks[$class]);
        } else {
            $this->fakeTasks[$class] = $remaining;
        }
    }

    /**
     * @param string|array $tasks
     * @param int|Closure|null $times
     * @return array<string, int|Closure|null>
     */
    protected function normalizeFakeTasks(string|array $tasks, int|Closure|null $times): array
    {
        if (is_string($tasks)) {
            if ($times instanceof Closure) {
                return [$tasks => $times];
            }

            if (is_int($times)) {
                return [$tasks => max(0, abs($times))];
            }

            return [$tasks => 1];
        }

        $normalized = [];

        if (array_is_list($tasks)) {
            foreach ($tasks as $class) {
                $normalized[$class] = 1;
            }

            return $normalized;
        }

        foreach ($tasks as $class => $value) {
            if (is_int($class)) {
                $normalized[(string) $value] = 1;

                continue;
            }

            if ($value instanceof Closure) {
                $normalized[$class] = $value;

                continue;
            }

            if (is_int($value)) {
                $normalized[$class] = max(0, abs($value));

                continue;
            }

            $normalized[$class] = $value === null ? null : 1;
        }

        return $normalized;
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
