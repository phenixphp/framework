<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\Session as ServerSession;
use Amp\Http\Server\Trailers;
use League\Uri\Components\Query;
use Phenix\Constants\ContentType;
use Phenix\Constants\RequestMode;
use Phenix\Contracts\Arrayable;
use Phenix\Contracts\Http\Requests\BodyParser;
use Phenix\Http\Requests\Concerns\HasCookies;
use Phenix\Http\Requests\Concerns\HasHeaders;
use Phenix\Http\Requests\Concerns\HasQueryParameters;
use Phenix\Http\Requests\FormParser;
use Phenix\Http\Requests\JsonParser;
use Phenix\Http\Requests\RouteAttributes;
use Phenix\Http\Requests\StreamParser;
use Psr\Http\Message\UriInterface;

class Request implements Arrayable
{
    use HasHeaders;
    use HasCookies;
    use HasQueryParameters;

    protected readonly BodyParser $body;
    protected readonly Query $query;
    protected readonly RouteAttributes|null $attributes;
    protected Session|null $session;

    public function __construct(protected ServerRequest $request)
    {
        $attributes = [];
        $this->session = null;

        if ($request->hasAttribute(Router::class)) {
            $attributes = $request->getAttribute(Router::class);
        }

        if ($request->hasAttribute(ServerSession::class)) {
            $this->session = new Session($request->getAttribute(ServerSession::class));
        }

        $this->query = Query::fromUri($request->getUri());
        $this->attributes = new RouteAttributes($attributes);
        $this->body = $this->getParser();
    }

    public function getClient(): Client
    {
        return $this->request->getClient();
    }

    public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    public function getBody(): RequestBody
    {
        return $this->request->getBody();
    }

    public function setBody(ReadableStream|string $body): void
    {
        $this->request->setBody($body);
    }

    public function getTrailers(): Trailers|null
    {
        return $this->request->getTrailers();
    }

    public function setTrailers(Trailers $trailers): void
    {
        $this->request->setTrailers($trailers);
    }

    public function removeTrailers(): void
    {
        $this->request->removeTrailers();
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function isIdempotent(): bool
    {
        return $this->request->isIdempotent();
    }

    public function route(string|null $key = null, string|int|null $default = null): RouteAttributes|string|int|null
    {
        if ($key) {
            return $this->attributes->get($key, $default);
        }

        return $this->attributes;
    }

    public function query(string|null $key = null, array|string|int|null $default = null): Query|array|string|null
    {
        if ($key) {
            return $this->query->parameter($key) ?? $default;
        }

        return $this->query;
    }

    public function body(string|null $key = null, array|string|int|null $default = null): BodyParser|BufferedFile|array|string|int|null
    {
        if ($key) {
            return $this->body->hasFile($key) ? $this->body->getFile($key) : $this->body->get($key, $default);
        }

        return $this->body;
    }

    public function session(string|null $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return $this->session?->get($key, $default);
        }

        return $this->session;
    }

    public function toArray(): array
    {
        return $this->body->toArray();
    }

    protected function mode(): RequestMode
    {
        return RequestMode::BUFFERED;
    }

    protected function getParser(): BodyParser
    {
        $contentType = ContentType::fromValue($this->request->getHeader('content-type'));

        if ($contentType === ContentType::JSON) {
            return JsonParser::fromRequest($this->request);
        }

        if ($this->mode() === RequestMode::STREAMED) {
            return StreamParser::fromRequest($this->request, [
                'body_size_limit' => 120 * 1024 * 1024,
            ]);
        }

        return FormParser::fromRequest($this->request);
    }
}
