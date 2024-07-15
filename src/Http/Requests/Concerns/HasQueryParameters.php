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
        $parameters = [];

        foreach ($this->request->getQueryParameters() as $key => $value) {
            if (str_contains($key, '[')) {
                [$key, $value] = $this->parseParameterArray($key, $value);

                $values = $parameters[$key] ?? [];
                $parameters[$key] = [...$values, ...$value];
            } else {
                $parameters[$key] = $value[0];
            }
        }

        return $parameters;
    }

    private function parseParameterArray(string $key, array $value): array
    {
        if (str_contains($key, '[]')) {
            return [
                str_replace('[]', '', $key),
                $value,
            ];
        }

        preg_match('/\[([a-zA-Z]+)\]/', $key, $matches);

        $childKey = $matches[1];

        return [
            str_replace($matches[0], '', $key),
            [$childKey => $value[0]],
        ];
    }
}
