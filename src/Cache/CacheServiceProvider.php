<?php

declare(strict_types=1);

namespace Phenix\Cache;

use Phenix\Cache\Console\CacheClear;
use Phenix\Providers\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            CacheManager::class,
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $this->bind(CacheManager::class)
            ->setShared(true);
    }

    public function boot(): void
    {
        $this->commands([
            CacheClear::class,
        ]);
    }
}
