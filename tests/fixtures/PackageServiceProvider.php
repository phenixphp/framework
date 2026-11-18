<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Phenix\Providers\ServiceProvider;

class PackageServiceProvider extends ServiceProvider
{
    public function mergePackageConfig(string $path, string $key): void
    {
        $this->mergeConfigFrom($path, $key);
    }

    public function loadPackageRoutes(string $path): void
    {
        $this->loadRoutesFrom($path);
    }

    public function loadPackageMigrations(array|string $paths): void
    {
        $this->loadMigrationsFrom($paths);
    }

    public function loadPackageViews(array|string $paths, string $namespace): void
    {
        $this->loadViewsFrom($paths, $namespace);
    }

    public function loadPackageTranslations(string $path, string $namespace): void
    {
        $this->loadTranslationsFrom($path, $namespace);
    }

    public function publishPackagePaths(array $paths, array|string|null $groups = null): void
    {
        $this->publishes($paths, $groups);
    }

    public function publishPackageMigrations(array $paths, array|string|null $groups = null): void
    {
        $this->publishesMigrations($paths, $groups);
    }
}
