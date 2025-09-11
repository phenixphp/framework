<?php

declare(strict_types=1);

namespace Phenix\Events;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Phenix\Events\Contracts\EventEmitter as EventEmitterContract;

class EventServiceProvider extends AbstractServiceProvider
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
}
