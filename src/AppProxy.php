<?php

declare(strict_types=1);

namespace Phenix;

use Phenix\Contracts\App as AppContract;

class AppProxy implements AppContract
{
    private bool $testingMode;

    public function __construct(
        private App $app
    ) {
        $this->testingMode = false;
    }

    public function run(): void
    {
        if ($this->testingMode) {
            $this->app->disableSignalTrapping();
        }

        $this->app->run();
    }

    public function stop(): void
    {
        $this->app->stop();
    }

    public function swap(string $key, object $concrete): void
    {
        $this->app->swap($key, $concrete);
    }

    public function register(string $key, mixed $concrete = null): void
    {
        $this->app->register($key, $concrete);
    }

    public function enableTestingMode(): self
    {
        $this->testingMode = true;

        return $this;
    }
}
