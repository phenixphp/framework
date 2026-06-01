<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Amp\Http\Server\Response as ServerResponse;
use Amp\Http\Server\Trailers;
use Closure;
use InvalidArgumentException;
use Phenix\Contracts\Arrayable;
use Phenix\Facades\View;
use Phenix\Http\Constants\HttpStatus;

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

        $this->body = json_encode($content);
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

    public function redirect(string $location, HttpStatus $status = HttpStatus::FOUND, array $headers = []): self
    {
        $this->body = json_encode(['redirectTo' => $location]);
        $this->status = $status;
        $this->headers = [...['Location' => $location, 'content-type' => 'application/json'], ...$headers];

        return $this;
    }

    /**
     * @param Closure(): iterable<int, ServerSentEvent|string>|iterable<int, ServerSentEvent|string> $events
     */
    public function eventStream(
        Closure|iterable $events,
        HttpStatus $status = HttpStatus::OK,
        array $headers = []
    ): self {
        $this->body = new ReadableIterableStream($this->formatEventStream($this->resolveEventStream($events)));
        $this->status = $status;
        $this->headers = [
            ...[
                'content-type' => 'text/event-stream; charset=utf-8',
                'cache-control' => 'no-cache',
            ],
            ...$headers,
        ];

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

    /**
     * @param Closure(): iterable<int, ServerSentEvent|string>|iterable<int, ServerSentEvent|string> $events
     * @return iterable<int, ServerSentEvent|string>
     */
    protected function resolveEventStream(Closure|iterable $events): iterable
    {
        if (! $events instanceof Closure) {
            return $events;
        }

        $events = $events();

        if (! is_iterable($events)) {
            throw new InvalidArgumentException('The event stream closure must return an iterable.');
        }

        return $events;
    }

    /**
     * @param iterable<int, ServerSentEvent|string> $events
     * @return iterable<int, string>
     */
    protected function formatEventStream(iterable $events): iterable
    {
        foreach ($events as $event) {
            yield $event instanceof ServerSentEvent
                ? (string) $event
                : $this->normalizeEventFrame($event);
        }
    }

    protected function normalizeEventFrame(string $event): string
    {
        if (str_ends_with($event, "\n\n") || str_ends_with($event, "\r\n\r\n")) {
            return $event;
        }

        return rtrim($event, "\r\n") . "\n\n";
    }
}
