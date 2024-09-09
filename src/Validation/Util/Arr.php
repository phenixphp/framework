<?php

declare(strict_types=1);

namespace Phenix\Validation\Util;

use Closure;

class Arr extends Utility
{
    public static function every(array $definition, Closure $closure): bool
    {
        foreach ($definition as $key => $value) {
            if (! $closure($value, $key)) {
                return false;
            }
        }

        return true;
    }
}
