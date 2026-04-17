<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ExceptionHandler as ExceptionHandlerContract;
use Amp\Http\Server\HttpErrorException;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Throwable;

class ExceptionHandler implements ExceptionHandlerContract
{
    public function __construct(
        private readonly ErrorHandler $errorHandler
    ) {
    }

    public function handleException(Request $request, Throwable $exception): Response
    {
        report($exception, [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'client' => $request->getClient()->getRemoteAddress()->toString(),
        ]);

        if ($exception instanceof HttpErrorException) {
            return $this->errorHandler->handleError(
                status: $exception->getStatus(),
                reason: $exception->getReason(),
                request: $request
            );
        }

        if (! $this->errorHandler->shouldExposeDebugDetails()) {
            return $this->errorHandler->handleError(status: HttpStatus::INTERNAL_SERVER_ERROR, request: $request);
        }

        return $this->errorHandler->json(payload: [
            'success' => false,
            'error' => $exception->getMessage(),
            'status' => HttpStatus::INTERNAL_SERVER_ERROR,
            'debug' => [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'path' => $request->getUri()->getPath(),
            ],
        ], status: HttpStatus::INTERNAL_SERVER_ERROR);
    }
}
