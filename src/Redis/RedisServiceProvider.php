<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Phenix\Database\Constants\Connection;
use Phenix\Providers\ServiceProvider;
use Phenix\Redis\Contracts\Client as ClientContract;

class RedisServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            ClientContract::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(ClientContract::class, fn (): ClientContract => new Client(
            $this->getContainer()->get(Connection::redis('default'))
        ))->setShared(true);
    }
}
