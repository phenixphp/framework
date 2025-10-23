<?php

declare(strict_types=1);

namespace Phenix\Queue\Concerns;

use Closure;
use Phenix\App;
use Phenix\Data\Collection;
use Phenix\Tasks\QueuableTask;
use Phenix\Testing\Constants\FakeMode;
use Phenix\Util\Date;
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
     * @var array<string>
     */
    protected array $fakeExceptTasks = [];

    /**
     * @var Collection<array{task_class: class-string<QueuableTask>, task: QueuableTask, queue: string|null, connection: string|null, timestamp: Date}>
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

        $this->enableFake(FakeMode::EXCEPT);

        $this->fakeExceptTasks[] = $taskClass;
        $this->fakeTasks = [];
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
        $this->fakeExceptTasks = [];
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
            'timestamp' => Date::now(),
        ]);
    }

    protected function shouldFakeTask(QueuableTask $task): bool
    {
        if ($this->fakeMode === FakeMode::ALL) {
            return true;
        }

        if ($this->fakeMode === FakeMode::EXCEPT) {
            return ! in_array($task::class, $this->fakeExceptTasks, true);
        }

        $result = false;

        if (! empty($this->fakeTasks) && array_key_exists($task::class, $this->fakeTasks)) {
            $config = $this->fakeTasks[$task::class];

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
