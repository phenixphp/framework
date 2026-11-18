<?php

declare(strict_types=1);

namespace Phenix\Providers;

use League\Container\Definition\DefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Phenix\App;
use Phenix\Console\Phenix;
use Phenix\Facades\Config;
use Phenix\Packages\PackageRegistry;

use function in_array;
use function is_array;

abstract class ServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function __construct(protected array $provided = [])
    {
        // ..
    }

    public function provides(string $id): bool
    {
        return $this->isProvided($id);
    }

    public function register(): void
    {
        // ..
    }

    public function boot(): void
    {
        // ..
    }

    public function bind(string $key, mixed $concrete = null): DefinitionInterface
    {
        return $this->getContainer()->add($key, $concrete);
    }

    protected function isProvided(string $id): bool
    {
        return in_array($id, $this->provided, true);
    }

    protected function commands(array|string $commands): void
    {
        Phenix::pushCommands((array) $commands);
    }

    protected function mergeConfigFrom(string $path, string $key): void
    {
        $defaults = require $path;

        if (! is_array($defaults)) {
            return;
        }

        Config::set($key, array_merge($defaults, (array) Config::get($key, [])));
    }

    protected function loadRoutesFrom(string $path): void
    {
        if (is_file($path)) {
            require $path;
        }
    }

    protected function loadMigrationsFrom(array|string $paths): void
    {
        $this->packages()->addMigrationPaths($paths);
    }

    protected function loadViewsFrom(array|string $paths, string $namespace): void
    {
        $this->packages()->addViewPaths($paths, $namespace);
    }

    protected function loadTranslationsFrom(string $path, string $namespace): void
    {
        $this->packages()->addTranslationPath($path, $namespace);
    }

    protected function publishes(array $paths, array|string|null $groups = null): void
    {
        $this->packages()->addPublishPaths(static::class, $paths, $groups);
    }

    protected function publishesMigrations(array $paths, array|string|null $groups = null): void
    {
        $this->publishes($paths, $groups);
    }

    private function packages(): PackageRegistry
    {
        return App::make(PackageRegistry::class);
    }
}
