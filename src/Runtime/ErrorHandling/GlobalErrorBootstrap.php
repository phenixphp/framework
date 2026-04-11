<?php

declare(strict_types=1);

namespace Phenix\Runtime\ErrorHandling;

use ErrorException;
use Throwable;

use function in_array;

class GlobalErrorBootstrap
{
    private const FATAL_ERRORS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
    ];

    private static bool $registered = false;

    private static bool $active = false;

    /**
     * @var callable|null
     */
    private static $previousExceptionHandler = null;

    public static function register(): void
    {
        if (self::$active) {
            return;
        }

        self::$active = true;
        set_error_handler(self::handleError(...));
        self::$previousExceptionHandler = set_exception_handler(self::handleException(...));

        if (! self::$registered) {
            register_shutdown_function(self::handleShutdown(...));
            self::$registered = true;
        }
    }

    public static function restore(): void
    {
        if (! self::$active) {
            return;
        }

        restore_error_handler();
        restore_exception_handler();

        self::$previousExceptionHandler = null;
        self::$active = false;
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        $exception = new ErrorException($message, 0, $severity, $file, $line);

        report($exception, [
            'severity' => $severity,
            'source' => 'php-error',
        ]);

        throw $exception;
    }

    public static function handleException(Throwable $exception): void
    {
        report($exception, [
            'source' => 'uncaught-exception',
        ]);

        if (self::$previousExceptionHandler !== null) {
            (self::$previousExceptionHandler)($exception);
        }
    }

    public static function handleShutdown(): void
    {
        if (! self::$active) {
            return;
        }

        $error = error_get_last();

        if ($error === null || ! in_array($error['type'], self::FATAL_ERRORS, true)) {
            return;
        }

        report(new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ), [
            'severity' => $error['type'],
            'source' => 'fatal-error',
        ]);
    }
}
