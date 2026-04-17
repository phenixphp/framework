<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\ByteStream\BufferException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\HttpErrorException;
use Amp\Http\Server\Request;

use function is_numeric;

class JsonParser extends BodyParser
{
    private array $body;

    public function __construct(
        private readonly int $bodySizeLimit = 120 * 1024 * 1024
    ) {
        $this->body = [];
    }

    public static function fromRequest(Request $request, array $options = []): self
    {
        return (new self($options['body_size_limit'] ?? 120 * 1024 * 1024))->parse($request);
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
        try {
            $raw = $request->getBody()->buffer(limit: $this->bodySizeLimit);
        } catch (BufferException $exception) {
            throw new HttpErrorException(HttpStatus::PAYLOAD_TOO_LARGE, 'Request body is too large', $exception);
        }

        $body = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($body)) {
            $this->body = $body;
        }

        return $this;
    }
}
