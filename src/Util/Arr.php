<?php

declare(strict_types=1);

namespace Phenix\Util;

use Closure;

use function array_filter;
use function implode;
use function is_array;
use function is_null;

class Arr extends Utility
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

    public static function every(array $definition, Closure $closure): bool
    {
        foreach ($definition as $key => $value) {
            if (! $closure($value, $key)) {
                return false;
            }
        }

        return true;
    }

    public static function first(array $data, Closure|null $closure = null, mixed $default = null): mixed
    {
        if ($closure) {
            foreach ($data as $key => $value) {
                if ($closure($value, $key)) {
                    return $value;
                }
            }

            return $default;
        }

        if (array_is_list($data)) {
            return $data[0] ?? $default;
        }

        return array_values($data)[0] ?? $default;
    }

    /**
     * Laravel team credits
     */
    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}
