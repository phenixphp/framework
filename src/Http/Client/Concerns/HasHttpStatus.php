<?php

declare(strict_types=1);

namespace Phenix\Http\Client\Concerns;

use Phenix\Http\Constants\HttpStatus;

trait HasHttpStatus
{
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

    private function hasStatus(HttpStatus $status): bool
    {
        return $this->status() === $status->value;
    }
}
