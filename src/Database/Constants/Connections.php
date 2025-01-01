<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

class Connections
{
    public const PREFIX = 'database.connections.';
    public const REDIS = 'redis.connections.';

    public static function default(): string
    {
        return self::name('default');
    }

    public static function name(string $connection): string
    {
        return self::PREFIX . $connection;
    }

    public static function redis(string $connection): string
    {
        return self::REDIS . $connection;
    }
}
