<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Phenix\Tasks\Console\MakeTask;
use Phenix\Providers\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Task::setBootingSettings();

        $this->commands([
            MakeTask::class,
        ]);
    }
}
