<?php

declare(strict_types=1);

namespace Phenix\Queue\Concerns;

use Closure;
use Phenix\App;
use Phenix\Tasks\QueuableTask;
use Throwable;

trait CaptureTasks
{
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

    protected function shouldFakeTask(QueuableTask $task): bool
    {
        if (! $this->faking) {
            return false;
        }

        $result = false;

        if ($this->fakeAll) {
            $result = true;
        } else {
            $class = $task::class;

            if (! empty($this->fakeTasks) && array_key_exists($class, $this->fakeTasks)) {
                $config = $this->fakeTasks[$class];

                if ($config instanceof Closure) {
                    try {
                        $result = (bool) $config($this->pushed);
                    } catch (Throwable $e) {
                        report($e);
                        $result = false;
                    }
                } else {
                    $result = $config === null || $config > 0;
                }
            }
        }

        return $result;
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
        $normalized = [];

        if (is_string($tasks)) {
            if ($times instanceof Closure) {
                $normalized[$tasks] = $times;
            } elseif (is_int($times)) {
                $normalized[$tasks] = max(0, abs($times));
            } else {
                $normalized[$tasks] = 1;
            }

            return $normalized;
        }

        if (array_is_list($tasks)) {
            foreach ($tasks as $class) {
                $normalized[$class] = 1;
            }
        } else {
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
        }

        return $normalized;
    }
}
