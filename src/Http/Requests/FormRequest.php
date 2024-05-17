<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Components\Query;
use Phenix\Constants\ContentType;
use Phenix\Contracts\Arrayable;
use Phenix\Contracts\Http\Requests\BodyParser;

class FormRequest implements Arrayable
{
    protected readonly BodyParser $body;
    protected readonly Query $query;
    protected readonly RouteAttributes $attributes;

    public function __construct(Request $request, ContentType $contentType)
    {
        $attributes = [];

        if ($request->hasAttribute(Router::class)) {
            $attributes = $request->getAttribute(Router::class);
        }

        $this->query = Query::fromUri($request->getUri());
        $this->attributes = new RouteAttributes($attributes);
        $this->body = match ($contentType) {
            ContentType::JSON => JsonParser::fromRequest($request),
            default => FormParser::fromRequest($request),
        };
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request, ContentType::fromValue($request->getHeader('content-type')));
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

    public function body(string|null $key = null, array|string|int|null $default = null): BodyParser|array|string|int|null
    {
        if ($key) {
            return $this->body->get($key, $default);
        }

        return $this->body;
    }

    public function toArray(): array
    {
        return $this->body->toArray();
    }
}
