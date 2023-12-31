<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Routing\Route;
use Phenix\Util\Directory;
use Phenix\Util\NamespaceResolver;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->bind(Route::class)->setShared(true);

        $this->registerControllers();
        $this->loadRoutes();
    }

    private function registerControllers(): void
    {
        $controllers = Directory::all(self::getControllersPath());

        foreach ($controllers as $controller) {
            $controller = NamespaceResolver::parse($controller);

            $this->bind($controller);
        }
    }

    private function getControllersPath(): string
    {
        return base_path('app'. DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers');
    }

    private function loadRoutes(): void
    {
        foreach (Directory::all(base_path('routes')) as $file) {
            require $file;
        }
    }
}
