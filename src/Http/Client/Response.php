<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Response as ClientResponse;
use Closure;
use Phenix\Data\Collection;
use Phenix\Http\Client\Concerns\HasHttpStatus;
use Phenix\Http\Client\Exceptions\RequestException;
use Phenix\Util\Arr;

use function is_array;

class Response
{
    use HasHttpStatus;

    protected readonly string $body;

    public function __construct(private readonly ClientResponse $response)
    {
        $this->body = $this->response->getBody()->buffer();
    }

    public function getClientResponse(): ClientResponse
    {
        return $this->response;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function json(string|null $key = null, Closure|array|string|null $default = null, int $flags = 0): mixed
    {
        $data = json_decode($this->body, true, flags: $flags);

        if ($data === null) {
            return value($default);
        }

        if ($key === null) {
            return $data;
        }

        if (! is_array($data)) {
            return value($default);
        }

        return Arr::get($data, $key, $default);
    }

    public function object(): object
    {
        return (object) (json_decode($this->body) ?? []);
    }

    public function collect(string|null $key = null): Collection
    {
        $data = $this->json($key, []);

        return Collection::fromArray(is_array($data) ? $data : [$data]);
    }

    public function status(): int
    {
        return $this->response->getStatus();
    }

    public function successful(): bool
    {
        return $this->response->isSuccessful();
    }

    public function redirect(): bool
    {
        return $this->response->isRedirect();
    }

    public function failed(): bool
    {
        return $this->clientError() || $this->response->isServerError();
    }

    public function clientError(): bool
    {
        return $this->response->isClientError();
    }

    public function serverError(): bool
    {
        return $this->response->isServerError();
    }

    public function header(string $header): string|null
    {
        return $this->response->getHeader($header);
    }

    public function headers(): array
    {
        return $this->response->getHeaders();
    }

    public function onError(Closure $closure): self
    {
        if ($this->failed()) {
            $closure($this);
        }

        return $this;
    }

    public function throw(): self
    {
        if ($this->failed()) {
            throw new RequestException($this);
        }

        return $this;
    }

    public function throwIf(Closure|bool $condition): self
    {
        $condition = $condition instanceof Closure ? $condition($this) : $condition;

        if ($condition) {
            return $this->throw();
        }

        return $this;
    }
}
