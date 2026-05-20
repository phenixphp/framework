<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\Http\Client\Response as ClientResponse;
use Closure;
use Phenix\Facades\File;
use Phenix\Http\Constants\HttpStatus;

use function strlen;

class StreamResponse
{
    public function __construct(private readonly ClientResponse $response)
    {
    }

    public function getClientResponse(): ClientResponse
    {
        return $this->response;
    }

    public function read(): string|null
    {
        return $this->response->getBody()->read();
    }

    public function each(Closure $closure): self
    {
        $bytes = 0;

        while (($chunk = $this->read()) !== null) {
            $bytes += strlen($chunk);
            $closure($chunk, $bytes, $this);
        }

        return $this;
    }

    public function save(string $path, Closure|null $progress = null): int
    {
        $file = File::openFile($path, 'w');
        $bytes = 0;

        try {
            while (($chunk = $this->read()) !== null) {
                $file->write($chunk);
                $bytes += strlen($chunk);

                if ($progress !== null) {
                    $progress($bytes, $chunk, $this);
                }
            }
        } finally {
            $file->close();
        }

        return $bytes;
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
        return $this->response->isClientError() || $this->response->isServerError();
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

    private function hasStatus(HttpStatus $status): bool
    {
        return $this->status() === $status->value;
    }
}
