<?php

declare(strict_types=1);

namespace Phenix\Util;

use Phenix\App;

class NamespaceResolver extends Utility
{
    public static function parse(string $path): string
    {
        $base = App::path() . DIRECTORY_SEPARATOR;

        $namespace = str_replace([$base, '.php', '/'], ['', '', '\\'], $path);

        return ucfirst($namespace);
    }
}
