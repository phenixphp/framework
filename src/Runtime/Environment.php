<?php

declare(strict_types=1);

namespace Phenix\Runtime;

use Dotenv\Dotenv;

class Environment
{
    public static function load(string|null $fileName = null, string|null $environment = null): void
    {
        $fileName ??= '.env';
        $fileName .= $environment ? ".{$environment}" : '';
        $fileNamePath = base_path() . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($fileNamePath)) {
            Dotenv::createImmutable(base_path(), $fileName)->load();
        }
    }
}
