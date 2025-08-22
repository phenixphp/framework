<?php

declare(strict_types=1);

namespace Phenix\Redis\Contracts;

interface Client
{
    public function execute(string $command, string|int|float ...$args): mixed;
}
