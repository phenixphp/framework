<?php

declare(strict_types=1);

namespace Phenix\Providers;

use League\Container\Definition\DefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Phenix\Console\Phenix;

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
}
