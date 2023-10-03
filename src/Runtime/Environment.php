<?php

declare(strict_types=1);

namespace Phenix\Runtime;

use Dotenv\Dotenv;

class Environment
{
    public static function load(string|null $env = null): void
    {
        $base = '.env';
        $env = $env ? "{$base}.{$env}" : $base;

        if (file_exists($env)) {
            Dotenv::createImmutable(base_path(), $env)->load();
        }
    }
}
