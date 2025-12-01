<?php

declare(strict_types=1);

namespace Phenix\Redis\Contracts;

use Amp\Redis\RedisClient;

interface Client
{
    public function execute(string $command, string|int|float ...$args): mixed;

    public function getClient(): RedisClient;

    public function __call(string $name, array $arguments): mixed;
}
