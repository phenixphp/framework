<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Runtime\Facade;

/**
 * @method static array|string|int|bool|null get(string $key)
 * @method static void set(string $key, array|string|int|bool|null $value)
 *
 * @see \Phenix\Runtime\Config
 */
class Config extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Runtime\Config::class;
    }
}
