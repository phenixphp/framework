<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Amp\Future;
use Closure;
use Phenix\App;
use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Events\Contracts\EventListener;
use Phenix\Runtime\Facade;
use Phenix\Testing\TestEvent;

/**
 * @method static void on(string $event, Closure|EventListener|string $listener, int $priority = 0)
 * @method static void once(string $event, Closure|EventListener|string $listener, int $priority = 0)
 * @method static void off(string $event, Closure|EventListener|string|null $listener = null)
 * @method static array emit(string|EventContract $event, mixed $payload = null)
 * @method static Future emitAsync(string|EventContract $event, mixed $payload = null)
 * @method static array getListeners(string $event)
 * @method static bool hasListeners(string $event)
 * @method static void removeAllListeners()
 * @method static void setMaxListeners(int $maxListeners)
 * @method static int getMaxListeners()
 * @method static void setEmitWarnings(bool $emitWarnings)
 * @method static int getListenerCount(string $event)
 * @method static array getEventNames()
 * @method static void log()
 * @method static void fake(string|array|null $tasks = null)
 * @method static array getEventLog()
 * @method static \Phenix\Testing\TestEvent expect()
 *
 * @see \Phenix\Events\EventEmitter
 */
class Event extends Facade
{
    public static function getKeyName(): string
    {
        return \Phenix\Events\EventEmitter::class;
    }

    public static function expect(string $event): TestEvent
    {
        /** @var \Phenix\Events\EventEmitter $emitter */
        $emitter = App::make(self::getKeyName());

        return new TestEvent($event, $emitter->getEventLog());
    }
}
