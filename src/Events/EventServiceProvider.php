<?php

declare(strict_types=1);

namespace Phenix\Events;

use Phenix\Events\Console\MakeEvent;
use Phenix\Events\Console\MakeListener;
use Phenix\Events\Contracts\EventEmitter as EventEmitterContract;
use Phenix\Providers\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $provides = [
        EventEmitter::class,
        EventEmitterContract::class,
    ];

    public function provides(string $id): bool
    {
        return in_array($id, $this->provides);
    }

    public function register(): void
    {
        $this->getContainer()->addShared(EventEmitter::class, EventEmitter::class);
        $this->getContainer()->add(EventEmitterContract::class, EventEmitter::class);
    }

    public function boot(): void
    {
        $this->commands([
            MakeEvent::class,
            MakeListener::class,
        ]);
    }
}
