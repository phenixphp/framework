<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Console\Commands\MakeController;
use Phenix\Console\Commands\MakeMiddleware;
use Phenix\Console\Commands\MakeServiceProvider;
use Phenix\Console\Commands\MakeTest;

class CommandsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MakeTest::class,
            MakeController::class,
            MakeMiddleware::class,
            MakeServiceProvider::class,
        ]);
    }
}
