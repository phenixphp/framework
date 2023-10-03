<?php

declare(strict_types=1);

namespace Phenix\Database\Constants;

class Connections
{
    public const PREFIX = 'database.connections.';

    public static function default(): string
    {
        return self::name('default');
    }

    public static function name(string $connection): string
    {
        return self::PREFIX . $connection;
    }
}
