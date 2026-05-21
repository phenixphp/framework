<?php

declare(strict_types=1);

namespace Phenix\Http\Client;

use Amp\ByteStream\Payload;
use Amp\Http\Client\Response as ClientResponse;
use Closure;
use LogicException;
use Phenix\Facades\File;
use Phenix\Http\Constants\HttpStatus;
use Throwable;

use function strlen;

class StreamResponse
{
    private Payload $body;

    private bool $readLocked = false;

    public function __construct(private readonly ClientResponse $response)
    {
        $this->body = $response->getBody();
    }

    public function getClientResponse(): ClientResponse
    {
        return $this->response;
    }

    public function read(): string|null
    {
        if ($this->readLocked) {
            throw new LogicException('The response stream cannot be read from inside a streaming callback.');
        }

        return $this->body->read();
    }

    public function each(Closure $closure): self
    {
        $totalBytesRead = 0;

        while (($chunk = $this->read()) !== null) {
            $totalBytesRead += strlen($chunk);

            $this->readLocked = true;

            try {
                $closure($chunk, $totalBytesRead, $this);
            } finally {
                $this->readLocked = false;
            }
        }

        return $this;
    }

    public function save(string $path, Closure|null $progress = null): int
    {
        $completed = false;
        $totalBytesWritten = 0;
        $temporaryPath = $this->temporaryPath($path);
        $file = File::openFile($temporaryPath, 'w');

        try {
            while (($chunk = $this->read()) !== null) {
                $file->write($chunk);
                $totalBytesWritten += strlen($chunk);

                if ($progress !== null) {
                    $this->readLocked = true;

                    try {
                        $progress($totalBytesWritten, $chunk, $this);
                    } finally {
                        $this->readLocked = false;
                    }
                }
            }

            $completed = true;
        } finally {
            $file->close();

            if (! $completed) {
                $this->discardTemporaryFile($temporaryPath);
            }
        }

        try {
            File::move($temporaryPath, $path);
        } catch (Throwable $exception) {
            $this->discardTemporaryFile($temporaryPath);

            throw $exception;
        }

        return $totalBytesWritten;
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

    private function temporaryPath(string $path): string
    {
        return dirname($path) . DIRECTORY_SEPARATOR . '.phenix-stream-' . bin2hex(random_bytes(8)) . '.tmp';
    }

    private function discardTemporaryFile(string $temporaryPath): void
    {
        try {
            File::deleteFile($temporaryPath);
        } catch (Throwable) {
        }
    }
}
