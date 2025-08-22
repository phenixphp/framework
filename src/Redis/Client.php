<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Amp\Redis\RedisClient;
use Phenix\Redis\Contracts\Client as ClientContract;

class Client implements ClientContract
{
    private RedisClient $client;

    public function __construct(RedisClient $client)
    {
        $this->client = $client;
    }

    public function execute(string $command, string|int|float ...$args): mixed
    {
        return $this->client->execute($command, ...$args);
    }
}
