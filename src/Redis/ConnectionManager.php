<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Redis\Exceptions\UnknownConnection;

class ConnectionManager
{
    public function __construct(
        protected ClientWrapper $client
    ) {
    }

    public function connection(string $connection): self
    {
        if (Config::get("database.redis.connections.{$connection}") === null) {
            throw new UnknownConnection("Redis connection [{$connection}] not configured.");
        }

        $this->client = App::make(Connection::redis($connection));

        return $this;
    }

    public function client(): ClientWrapper
    {
        return $this->client;
    }

    public function execute(string $command, string|int|float ...$args): mixed
    {
        return $this->client->execute($command, ...$args);
    }
}
