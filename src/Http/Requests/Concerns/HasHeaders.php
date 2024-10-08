<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

use Phenix\Util\Arr;

use function is_string;

trait HasHeaders
{
    public function setHeaders(array $headers): void
    {
        $this->request->setHeaders($headers);
    }

    public function getHeaders(): array
    {
        return Arr::map($this->request->getHeaders(), function (array|string $value) {
            return is_string($value) ? $value : $value[0];
        });
    }

    public function setHeader(string $name, array|string $value): void
    {
        $this->request->setHeader($name, $value);
    }

    public function getHeader(string $name): string|null
    {
        return $this->request->getHeader($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function replaceHeaders(array $headers): void
    {
        $this->request->replaceHeaders($headers);
    }

    public function addHeader(string $name, array|string $value): void
    {
        $this->request->addHeader($name, $value);
    }

    public function removeHeader(string $name): void
    {
        $this->request->removeHeader($name);
    }
}
