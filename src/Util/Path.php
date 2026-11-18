<?php

declare(strict_types=1);

namespace Phenix\Util;

class Path extends Utility
{
    public static function join(string ...$segments): string
    {
        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
