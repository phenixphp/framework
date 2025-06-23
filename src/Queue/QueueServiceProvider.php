<?php

declare(strict_types=1);

namespace Phenix\Queue;

use Phenix\Queue\QueueManager;
use Phenix\Providers\ServiceProvider;
use Phenix\Queue\Console\TableCommand;

class QueueServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            QueueManager::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(QueueManager::class)
            ->setShared(true);
    }

    public function boot(): void
    {
        $this->commands([
            TableCommand::class,
        ]);
    }
}
