<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Response as ClientResponse;
use Closure;
use Phenix\Data\Collection;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Util\Arr;

use function is_array;

class Response
{
    private readonly string $body;

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

    public function ok(): bool
    {
        return $this->hasStatus(HttpStatus::OK);
    }

    public function created(): bool
    {
        return $this->hasStatus(HttpStatus::CREATED);
    }

    public function accepted(): bool
    {
        return $this->hasStatus(HttpStatus::ACCEPTED);
    }

    public function noContent(): bool
    {
        return $this->hasStatus(HttpStatus::NO_CONTENT) && $this->body === '';
    }

    public function movedPermanently(): bool
    {
        return $this->hasStatus(HttpStatus::MOVED_PERMANENTLY);
    }

    public function found(): bool
    {
        return $this->hasStatus(HttpStatus::FOUND);
    }

    public function badRequest(): bool
    {
        return $this->hasStatus(HttpStatus::BAD_REQUEST);
    }

    public function unauthorized(): bool
    {
        return $this->hasStatus(HttpStatus::UNAUTHORIZED);
    }

    public function paymentRequired(): bool
    {
        return $this->hasStatus(HttpStatus::PAYMENT_REQUIRED);
    }

    public function forbidden(): bool
    {
        return $this->hasStatus(HttpStatus::FORBIDDEN);
    }

    public function notFound(): bool
    {
        return $this->hasStatus(HttpStatus::NOT_FOUND);
    }

    public function requestTimeout(): bool
    {
        return $this->hasStatus(HttpStatus::REQUEST_TIMEOUT);
    }

    public function conflict(): bool
    {
        return $this->hasStatus(HttpStatus::CONFLICT);
    }

    public function unprocessableEntity(): bool
    {
        return $this->hasStatus(HttpStatus::UNPROCESSABLE_ENTITY);
    }

    public function tooManyRequests(): bool
    {
        return $this->hasStatus(HttpStatus::TOO_MANY_REQUESTS);
    }

    public function onError(Closure $closure): self
    {
        if ($this->failed()) {
            $closure($this);
        }

        return $this;
    }
    }

    private function hasStatus(HttpStatus $status): bool
    {
        return $this->status() === $status->value;
    }
}
