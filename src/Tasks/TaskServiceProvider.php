<?php

declare(strict_types=1);

namespace Phenix\Tasks;

use Phenix\Providers\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Task::setBootingSettings();
    }
}
