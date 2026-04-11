<?php

declare(strict_types=1);

use Phenix\Facades\Log;
use Phenix\Runtime\ErrorHandling\GlobalErrorHandler;

afterEach(function (): void {
    GlobalErrorHandler::restore();
});

it('reports and converts reportable PHP errors to exceptions', function (): void {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Invalid runtime state'
                && $context['exception'] === ErrorException::class
                && $context['severity'] === E_WARNING
                && $context['source'] === 'php-error';
        });

    GlobalErrorHandler::handleError(E_WARNING, 'Invalid runtime state', __FILE__, __LINE__);
})->throws(ErrorException::class);

it('reports uncaught exceptions', function (): void {
    $exception = new RuntimeException('Unhandled runtime failure');

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($exception): bool {
            return $message === 'Unhandled runtime failure'
                && $context['exception'] === RuntimeException::class
                && $context['file'] === $exception->getFile()
                && $context['line'] === $exception->getLine()
                && is_string($context['trace'])
                && $context['source'] === 'uncaught-exception';
        });

    GlobalErrorHandler::handleException($exception);
});

it('delegates uncaught exceptions to the previous exception handler', function (): void {
    $delegated = false;
    $exception = new RuntimeException('Delegated runtime failure');

    set_exception_handler(function (Throwable $handled) use (&$delegated, $exception): void {
        $delegated = $handled === $exception;
    });

    GlobalErrorHandler::register();

    try {
        Log::shouldReceive('error')->once();

        GlobalErrorHandler::handleException($exception);

        expect($delegated)->toBeTrue();
    } finally {
        GlobalErrorHandler::restore();
        restore_exception_handler();
    }
});

it('does not report shutdown errors when bootstrap is inactive', function (): void {
    Log::shouldReceive('error')->never();

    GlobalErrorHandler::handleShutdownError([
        'type' => E_ERROR,
        'message' => 'Fatal failure',
        'file' => __FILE__,
        'line' => __LINE__,
    ]);
});

it('does not report non fatal shutdown errors', function (): void {
    GlobalErrorHandler::register();

    Log::shouldReceive('error')->never();

    GlobalErrorHandler::handleShutdownError([
        'type' => E_WARNING,
        'message' => 'Non fatal warning',
        'file' => __FILE__,
        'line' => __LINE__,
    ]);
});

it('reports fatal shutdown errors', function (int $severity): void {
    GlobalErrorHandler::register();

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($severity): bool {
            return $message === 'Fatal shutdown failure'
                && $context['exception'] === ErrorException::class
                && $context['severity'] === $severity
                && $context['source'] === 'fatal-error';
        });

    GlobalErrorHandler::handleShutdownError([
        'type' => $severity,
        'message' => 'Fatal shutdown failure',
        'file' => __FILE__,
        'line' => __LINE__,
    ]);
})->with([
    E_ERROR,
    E_PARSE,
    E_CORE_ERROR,
    E_COMPILE_ERROR,
]);
