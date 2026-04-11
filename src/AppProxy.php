<?php

declare(strict_types=1);

namespace Phenix;

use Phenix\Contracts\App as AppContract;
use Phenix\Facades\Config;
use Phenix\Runtime\ErrorHandling\GlobalErrorHandler;
use Throwable;

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
        $this->configureErrorReporting();

        GlobalErrorHandler::register();

        if ($this->testingMode) {
            $this->app->disableSignalTrapping();
        }

        try {
            $this->app->run();
        } catch (Throwable $exception) {
            GlobalErrorHandler::restore();

            throw $exception;
        }
    }

    public function stop(): void
    {
        $this->app->stop();

        GlobalErrorHandler::restore();
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

    private function configureErrorReporting(): void
    {
        $debug = Config::get('app.debug') === true;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');

        if ($debug) {
            ini_set('log_errors', '1');
        }
    }
}
