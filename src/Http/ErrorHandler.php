<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler as ErrorHandlerContract;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Phenix\Facades\Config;

class ErrorHandler implements ErrorHandlerContract
{
    public function handleError(int $status, ?string $reason = null, ?Request $request = null): Response
    {
        $message = $reason ?? HttpStatus::getReason($status);
        $payload = [
            'success' => false,
            'error' => $message,
            'status' => $status,
        ];

        if ($this->shouldExposeDebugDetails()) {
            $payload['debug'] = [
                'reason' => $message,
                'path' => $request?->getUri()->getPath(),
            ];
        }

        return $this->json($payload, $status, $reason);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function json(array $payload, int $status, ?string $reason = null): Response
    {
        $response = new Response(
            headers: [
                'content-type' => 'application/json',
            ],
            body: json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $response->setStatus($status, $reason);

        return $response;
    }

    public function shouldExposeDebugDetails(): bool
    {
        return Config::get('app.debug') === true;
    }
}
