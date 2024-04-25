<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use Phenix\Contracts\Arrayable;

class FormRequest implements Arrayable
{
    public function __construct(protected array $parameters)
    {
        // ..
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->getAttribute(Router::class));
    }

    public function get(string $key, string|int|null $default = null): string|int|null
    {
        return $this->parameters[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    public function set(string $key, string|int $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    public function integer(string $key): int|null
    {
        return $this->has($key) ? (int) $this->get($key) : null;
    }

    public function toArray(): array
    {
        return $this->parameters;
    }
}
