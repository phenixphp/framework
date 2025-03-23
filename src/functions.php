<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Facades\Log;
use Phenix\Http\Response;

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
    function env(string $key, Closure|null $default = null): array|string|int|bool|null
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

if (! function_exists('value')) {
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('report')) {
    function report(Throwable $e): void
    {
        Log::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}

if (! function_exists('e')) {
    function e(Stringable|string|null $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}
