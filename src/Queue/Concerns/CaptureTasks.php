<?php

declare(strict_types=1);

namespace Phenix\Queue\Concerns;

use Closure;
use Phenix\App;
use Phenix\Data\Collection;
use Phenix\Tasks\QueuableTask;
use Phenix\Testing\Constants\FakeMode;
use Throwable;

trait CaptureTasks
{
    protected bool $logging = false;

    protected FakeMode $fakeMode = FakeMode::NONE;

    /**
     * @var array<string, int|null|Closure>
     */
    protected array $fakeTasks = [];

    /**
     * @var Collection<int, array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: float}>
     */
    protected Collection $pushed;

    public function log(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableLog();
    }

    public function fake(): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::ALL);
    }

    public function fakeWhen(string $taskClass, Closure $callback): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeTasks[$taskClass] = $callback;
    }

    public function fakeTimes(string $taskClass, int $times): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeTasks[$taskClass] = $times;
    }

    public function fakeOnce(string $taskClass): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeTasks[$taskClass] = 1;
    }

    public function fakeOnly(string $taskClass): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeTasks = [
            $taskClass => null,
        ];
    }

    public function fakeExcept(string $taskClass): void
    {
        if (App::isProduction()) {
            return;
        }

        $this->enableFake(FakeMode::SCOPED);

        $this->fakeTasks = [
            $taskClass => fn (Collection $log): bool => $log->filter(fn (array $entry): bool => $entry['task_class'] === $taskClass)->isEmpty(),
        ];
    }

    public function getQueueLog(): Collection
    {
        if (! isset($this->pushed)) {
            $this->pushed = Collection::fromArray([]);
        }

        return $this->pushed;
    }

    public function resetQueueLog(): void
    {
        $this->pushed = Collection::fromArray([]);
    }

    public function resetFaking(): void
    {
        $this->logging = false;
        $this->fakeMode = FakeMode::NONE;
        $this->fakeTasks = [];
        $this->pushed = Collection::fromArray([]);
    }

    protected function recordPush(QueuableTask $task): void
    {
        if (! $this->logging) {
            return;
        }

        $this->pushed->add([
            'task_class' => $task::class,
            'task' => $task,
            'queue' => $task->getQueueName(),
            'connection' => $task->getConnectionName(),
            'timestamp' => microtime(true),
        ]);
    }

    protected function shouldFakeTask(QueuableTask $task): bool
    {
        if ($this->fakeMode === FakeMode::ALL) {
            return true;
        }

        $result = false;
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

        return $result;
    }

    protected function consumeFakedTask(QueuableTask $task): void
    {
        $class = $task::class;

        if (! array_key_exists($class, $this->fakeTasks)) {
            return;
        }

        $remaining = $this->fakeTasks[$class];

        if (! $remaining || $remaining instanceof Closure) {
            return;
        }

        $remaining--;

        if ($remaining <= 0) {
            unset($this->fakeTasks[$class]);
        } else {
            $this->fakeTasks[$class] = $remaining;
        }
    }

    protected function enableLog(): void
    {
        if (! $this->logging) {
            $this->logging = true;
            $this->pushed = Collection::fromArray([]);
        }
    }

    protected function enableFake(FakeMode $fakeMode): void
    {
        $this->enableLog();
        $this->fakeMode = $fakeMode;
    }
}
