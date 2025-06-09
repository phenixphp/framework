<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Providers\ServiceProvider;
use Phenix\Queue\Console\TableCommand;

class QueueServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        // Register any queue-related services or bindings here.
    }

    public function boot(): void
    {
        $this->commands([
            TableCommand::class,
        ]);
    }
}
