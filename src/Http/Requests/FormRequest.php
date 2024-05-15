<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Components\Query;
use Phenix\Contracts\Arrayable;

class FormRequest implements Arrayable
{
    protected readonly Form $form;
    protected readonly Query $query;
    protected readonly RouteAttributes $attributes;

    public function __construct(Request $request)
    {
        $attributes = [];

        if ($request->hasAttribute(Router::class)) {
            $attributes = $request->getAttribute(Router::class);
        }

        $this->form = Form::fromRequest($request);
        $this->query = Query::fromUri($request->getUri());
        $this->attributes = new RouteAttributes($attributes);
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request);
    }

    public function route(string|null $key = null, string|int|null $default = null): RouteAttributes|string|int|null
    {
        if ($key) {
            return $this->attributes->get($key, $default);
        }

        return $this->attributes;
    }

    public function query(string|null $key = null, string|int|null $default = null): Query|array|string|null
    {
        if ($key) {
            return $this->query->parameter($key) ?? $default;
        }

        return $this->query;
    }

    // public function get(string $key, string|int|null $default = null): string|int|null
    // {
    //     return $this->parameters[$key] ?? $default;
    // }

    // public function has(string $key): bool
    // {
    //     return isset($this->parameters[$key]);
    // }

    // public function set(string $key, string|int $value): void
    // {
    //     $this->parameters[$key] = $value;
    // }

    // public function remove(string $key): void
    // {
    //     unset($this->parameters[$key]);
    // }

    // public function integer(string $key): int|null
    // {
    //     return $this->has($key) ? (int) $this->get($key) : null;
    // }

    public function toArray(): array
    {
        return [];
    }
}
