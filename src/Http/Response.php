<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Server\Response as ServerResponse;
use Amp\Http\Server\Trailers;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Contracts\Arrayable;
use Phenix\Facades\View;

class Response
{
    protected ReadableStream|string $body;
    protected HttpStatus $status;
    protected array $headers;
    protected Trailers|null $trailers;

    public function __construct()
    {
        $this->body = '';
        $this->status = HttpStatus::OK;
        $this->trailers = null;
    }

    public function plain(string $content, HttpStatus $status = HttpStatus::OK, array $headers = []): self
    {
        $this->body = $content;
        $this->status = $status;
        $this->headers = [...['content-type' => 'text/plain'], ...$headers];

        return $this;
    }

    /**
     * @param Arrayable|array<string|int, array|string|int|bool> $content
     */
    public function json(
        Arrayable|array $content = [],
        HttpStatus $status = HttpStatus::OK,
        array $headers = []
    ): self {
        if ($content instanceof Arrayable) {
            $content = $content->toArray();
        }

        $this->body = json_encode(['data' => $content]);
        $this->status = $status;
        $this->headers = [...['content-type' => 'application/json'], ...$headers];

        return $this;
    }

    public function view(
        string $template,
        array $data = [],
        HttpStatus $status = HttpStatus::OK,
        array $headers = []
    ): self {
        $this->body = View::view($template, $data)->render();
        $this->status = $status;
        $this->headers = [...['content-type' => 'text/html; charset=utf-8'], ...$headers];

        return $this;
    }

    public function send(): ServerResponse
    {
        return new ServerResponse(
            $this->status->value,
            $this->headers,
            $this->body,
            $this->trailers
        );
    }
}
