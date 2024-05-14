<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Phenix\Contracts\Arrayable;

class RouteAttributes implements Arrayable
{
    public function __construct(protected readonly array $attributes)
    {
        // ..
    }

    public function get(string $key, string|int|null $default = null): string|int|null
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function integer(string $key): int|null
    {
        return $this->has($key) ? (int) $this->get($key) : null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
