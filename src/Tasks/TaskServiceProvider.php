<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Phenix\Providers\ServiceProvider;
use Phenix\Tasks\Console\MakeTask;

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
