<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response as ServerResponse;
use Phenix\Contracts\Arrayable;

class Response
{
    public function plain(string $content, int $status = HttpStatus::OK): ServerResponse
    {
        return new ServerResponse($status, ['content-type' => 'text/plain'], $content);
    }

    /**
     * @param array<string|int, array|string|int|bool> $content
     */
    public function json(Arrayable|array $content = [], int $status = HttpStatus::OK): ServerResponse
    {
        if ($content instanceof Arrayable) {
            $content = $content->toArray();
        }

        $body = json_encode(['data' => $content]);

        return new ServerResponse($status, ['content-type' => 'application/json'], $body . PHP_EOL);
    }
}
