<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

trait HasQueryParameters
{
    public function setQueryParameter(string $key, array|string|null $value): void
    {
        $this->request->setQueryParameter($key, $value);
    }

    public function addQueryParameter(string $key, array|string|null $value): void
    {
        $this->request->addQueryParameter($key, $value);
    }

    public function setQueryParameters(array $parameters): void
    {
        $this->request->setQueryParameters($parameters);
    }

    public function replaceQueryParameters(array $parameters): void
    {
        $this->request->replaceQueryParameters($parameters);
    }

    public function removeQueryParameter(string $key): void
    {
        $this->request->removeQueryParameter($key);
    }

    public function removeQuery(): void
    {
        $this->request->removeQuery();
    }

    public function hasQueryParameter(string $key): bool
    {
        return $this->request->hasQueryParameter($key);
    }

    public function getQueryParameter(string $key): string|null
    {
        return $this->request->getQueryParameter($key);
    }

    public function getQueryParameters(): array
    {
        return $this->request->getQueryParameters();
    }
}
