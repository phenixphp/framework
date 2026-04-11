<?php

declare(strict_types=1);

use Phenix\Facades\Log;
use Phenix\Runtime\ErrorHandling\GlobalErrorBootstrap;

it('reports and converts reportable PHP errors to exceptions', function (): void {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Invalid runtime state'
                && $context['exception'] === ErrorException::class
                && $context['severity'] === E_WARNING
                && $context['source'] === 'php-error';
        });

    GlobalErrorBootstrap::handleError(E_WARNING, 'Invalid runtime state', __FILE__, __LINE__);
})->throws(ErrorException::class);
