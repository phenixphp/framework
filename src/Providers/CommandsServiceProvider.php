<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Console\Commands\GenerateKey;
use Phenix\Console\Commands\MakeCollection;
use Phenix\Console\Commands\MakeController;
use Phenix\Console\Commands\MakeMiddleware;
use Phenix\Console\Commands\MakeModel;
use Phenix\Console\Commands\MakeQuery;
use Phenix\Console\Commands\MakeRequest;
use Phenix\Console\Commands\MakeServiceProvider;
use Phenix\Console\Commands\MakeTest;

class CommandsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            MakeTest::class,
            MakeRequest::class,
            MakeController::class,
            MakeMiddleware::class,
            MakeModel::class,
            MakeCollection::class,
            MakeQuery::class,
            MakeServiceProvider::class,
            GenerateKey::class,
        ]);
    }
}
