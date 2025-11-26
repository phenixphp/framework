<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;

/**
 * @method static \Phenix\Redis\ConnectionManager connection(string $connection)
 * @method static mixed execute(string $command, string|int|float ...$args)
 *
 * @see \Phenix\Redis\ConnectionManager
 */
class Redis extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Redis\ConnectionManager::class;
    }
}
