<?php

declare(strict_types=1);

namespace Phenix\Packages;

class PackageRegistry
{
    private array $migrationPaths = [];

    private array $viewPaths = [];

    private array $translationPaths = [];

    private array $publishes = [];

    private array $publishGroups = [];

    public function addMigrationPaths(array|string $paths): void
    {
        $this->migrationPaths = array_values(array_unique([...$this->migrationPaths, ...(array) $paths]));
    }

    public function migrationPaths(): array
    {
        return $this->migrationPaths;
    }

    public function addViewPaths(array|string $paths, string $namespace): void
    {
        $this->viewPaths[$namespace] = array_values(array_unique([
            ...($this->viewPaths[$namespace] ?? []),
            ...(array) $paths,
        ]));
    }

    public function viewPaths(string $namespace): array
    {
        return $this->viewPaths[$namespace] ?? [];
    }

    public function addTranslationPath(string $path, string $namespace): void
    {
        $this->translationPaths[$namespace] = array_values(array_unique([
            ...($this->translationPaths[$namespace] ?? []),
            $path,
        ]));
    }

    public function translationPaths(): array
    {
        return $this->translationPaths;
    }

    public function addPublishPaths(string $provider, array $paths, array|string|null $groups = null): void
    {
        $this->publishes[$provider] = [...$this->publishes[$provider] ?? [], ...$paths];

        foreach ((array) $groups as $group) {
            $this->publishGroups[$group] = [...($this->publishGroups[$group] ?? []), ...$paths];
        }
    }

    public function pathsToPublish(string|null $provider = null, string|null $group = null): array
    {
        if ($provider !== null && $group !== null) {
            return array_intersect_key($this->publishes[$provider] ?? [], $this->publishGroups[$group] ?? []);
        }

        if ($provider !== null) {
            return $this->publishes[$provider] ?? [];
        }

        return $group !== null
            ? $this->publishGroups[$group] ?? []
            : $this->getPublishesPath();
    }

    public function publishableProviders(): array
    {
        return array_keys($this->publishes);
    }

    public function publishableGroups(): array
    {
        return array_keys($this->publishGroups);
    }

    protected function getPublishesPath(): array
    {
        $paths = $this->publishes ?: [[]];

        return [...array_values($paths)];
    }
}
