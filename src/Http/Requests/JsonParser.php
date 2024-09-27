<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\Request;

use function is_numeric;

class JsonParser extends BodyParser
{
    private array $body;

    public function __construct()
    {
        $this->body = [];
    }

    public static function fromRequest(Request $request, array $options = []): self
    {
        return (new self())->parse($request);
    }

    public function get(string $key, array|string|int|null $default = null): array|string|int|null
    {
        return $this->body[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]);
    }

    public function integer(string $key): int|null
    {
        if (! $this->has($key)) {
            return null;
        }

        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : null;
    }

    public function hasFile(string $key): bool
    {
        return false;
    }

    public function getFile(string $key, array|string|int|null $default = null): BufferedFile|null
    {
        return null;
    }

    public function files(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return $this->body;
    }

    protected function parse(Request $request): self
    {
        $body = json_decode($request->getBody()->read(), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->body = $body;
        }

        return $this;
    }
}
