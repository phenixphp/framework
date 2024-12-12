<?php

declare(strict_types=1);

namespace Phenix\Util;

use ArrayAccess;
use Closure;

use function array_filter;
use function implode;
use function is_array;
use function is_null;

/**
 * Laravel team credits
 *
 * @see https://github.com/laravel/framework/blob/master/src/Illuminate/Collections/Arr.php
 */
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
        $result = $default;

        if ($closure) {
            foreach ($data as $key => $value) {
                if ($closure($value, $key)) {
                    $result = $value;

                    break;
                }
            }
        } elseif (array_is_list($data)) {
            $result = $data[0] ?? $default;
        } else {
            $result = array_values($data)[0] ?? $default;
        }

        return $result;
    }

    public static function wrap(mixed $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    public static function set(array &$array, string|int $key, mixed $value): array
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function undot(array $array): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    public static function has(ArrayAccess|array $array, string|array $keys): bool
    {
        $keys = (array) $keys;

        if (! $array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (static::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    public static function exists(ArrayAccess|array $array, string|int $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        if (is_float($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $array);
    }

    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function get(ArrayAccess|array $array, string|int|null $key, mixed $default = null): mixed
    {
        $result = $default;

        if (static::accessible($array)) {
            if ($key === null) {
                $result = $array;
            } elseif (static::exists($array, $key)) {
                $result = $array[$key];
            } elseif (!str_contains($key, '.')) {
                $result = $array[$key] ?? value($default);
            } else {
                foreach (explode('.', $key) as $segment) {
                    if (static::accessible($array) && static::exists($array, $segment)) {
                        $array = $array[$segment];
                        $result = $array;
                    } else {
                        $result = value($default);
                        break;
                    }
                }
            }
        }

        return $result;
    }
}
