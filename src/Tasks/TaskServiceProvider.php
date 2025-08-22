<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Amp\Parallel\Worker\WorkerPool;
use Phenix\Providers\ServiceProvider;
use Phenix\Tasks\Console\MakeTask;

use function Amp\Parallel\Worker\workerPool;

class TaskServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            WorkerPool::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(WorkerPool::class, workerPool(...))
            ->setShared(true);
    }

    public function boot(): void
    {
        Task::setBootingSettings();

        $this->commands([
            MakeTask::class,
        ]);
    }
}
