<?php

declare(strict_types=1);

namespace Phenix\Views;

use Phenix\Util\Str;
use Phenix\Util\Utility;
use Phenix\Views\Exceptions\ViewNotFoundException;

class ViewName extends Utility
{
    public static function normalize(string $name): string
    {
        return Str::finish(str_replace('.', DIRECTORY_SEPARATOR, $name), '.php');
    }

    public static function clean(string $name): string
    {
        foreach (explode('/', $name) as $segment) {
            if ($segment === '' || str_ends_with($segment, '.') || str_ends_with($segment, ' ')
                || strpbrk($segment, "\\:\0") !== false) {
                throw new ViewNotFoundException("Invalid view name [{$name}].");
            }
        }

        return $name;
    }

    public static function ensure(string $name): string
    {
        return self::normalize(self::clean($name));
    }

    public static function template(string $path, string $base): string
    {
        $path = str_replace(Str::finish($base, DIRECTORY_SEPARATOR), '', $path);

        return str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $path);
    }
}
