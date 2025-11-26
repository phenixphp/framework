<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Providers\ServiceProvider;

class RedisServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            ConnectionManager::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(
            ConnectionManager::class,
            fn (): ConnectionManager => new ConnectionManager(App::make(Connection::redis('default')))
        );
    }
}
