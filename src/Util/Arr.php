<?php

declare(strict_types=1);

namespace Phenix\Util;

use Closure;

use function array_filter;
use function implode;
use function is_array;

class Arr
{
    /**
     * @param array<int, mixed> $data
     * @param string $separator
     * @return string
     */
    public static function implodeDeeply(array $data, string $separator = ' '): string
    {
        $data = array_filter($data, function ($value): bool {
            return ! empty($value) || $value === 0;
        });

        $data = array_map(function ($value) {
            return is_array($value) ? self::implodeDeeply($value) : $value;
        }, $data);

        return implode($separator, $data);
    }

    public static function map(array $data, Closure $closure): array
    {
        $keys = array_keys($data);
        $values = array_map($closure, array_values($data), $keys);

        return array_combine($keys, $values);
    }
}
