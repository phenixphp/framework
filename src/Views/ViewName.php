<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Util\Str;
use Phenix\Util\Utility;

class ViewName extends Utility
{
    public static function normalize(string $name): string
    {
        return Str::finish(str_replace('.', DIRECTORY_SEPARATOR, $name), '.php');
    }

    public static function clean(string $name): string
    {
        return str_replace(['..', '//'], '', $name);
    }

    public static function ensure(string $name): string
    {
        return self::normalize(self::clean($name));
    }
}
