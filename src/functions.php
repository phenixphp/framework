<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Facades\Config;
use Phenix\Facades\Log;
use Phenix\Facades\Translator;
use Phenix\Http\Response;
use Phenix\Routing\UrlGenerator;

if (! function_exists('base_path()')) {
    function base_path(string $path = ''): string
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        return App::path() . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
    }
}

if (! function_exists('response')) {
    function response(): Response
    {
        return new Response();
    }
}

if (! function_exists('env')) {
    function env(string $key, Closure|null $default = null): array|string|float|int|bool|null
    {
        $value = $_ENV[$key] ?? null;

        if ($value) {
            return match ($value) {
                'true' => true,
                'false' => false,
                default => $value,
            };
        }

        return $default instanceof Closure ? $default() : $default;
    }
}

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (! function_exists('route')) {
    function route(BackedEnum|string $name, array $parameters = [], bool $absolute = true): string
    {
        return App::make(UrlGenerator::class)->route($name, $parameters, $absolute);
    }

}

if (! function_exists('url')) {
    function url(string $path, array $parameters = [], bool $secure = false): string
    {
        return App::make(UrlGenerator::class)->to($path, $parameters, $secure);
    }
}

if (! function_exists('value')) {
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('report')) {
    function report(Throwable $e, array $context = []): void
    {
        Log::error($e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            ...$context,
        ]);
    }
}

if (! function_exists('e')) {
    function e(Stringable|string|null $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}

if (! function_exists('trans')) {
    function trans(string $key, array $replace = [], string|null $locale = null): array|string
    {
        return Translator::get($key, $replace, $locale);
    }
}

if (! function_exists('trans_choice')) {
    function trans_choice(string $key, int|array|Countable $number, array $replace = [], string|null $locale = null): string
    {
        return Translator::choice($key, $number, $replace, $locale);
    }
}

if (! function_exists('class_uses_recursive')) {
    function class_uses_recursive(object|string $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        do {
            $traits = class_uses($class) ?: [];

            foreach ($traits as $trait) {
                $results[$trait] = $trait;

                foreach (class_uses_recursive($trait) as $nestedTrait) {
                    $results[$nestedTrait] = $nestedTrait;
                }
            }
        } while ($class = get_parent_class($class));

        return array_values($results);
    }
}
