<?php

declare(strict_types=1);

use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Events\Event;
use Phenix\Events\EventEmitter;
use Phenix\Events\Exceptions\EventException;
use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Event as EventFacade;
use Phenix\Facades\Log;
use Tests\Unit\Events\Internal\InvalidListener;
use Tests\Unit\Events\Internal\StandardListener;

it('can register and emit basic events', function (): void {
    $emitter = new EventEmitter();
    $called = false;

    $emitter->on('test.event', function (EventContract $event) use (&$called): void {
        $called = true;
        expect($event->getName())->toBe('test.event');
        expect($event->getPayload())->toBe('test data');
    });

    $emitter->emit('test.event', 'test data');

    expect($called)->toBeTrue();
});

it('can register and emit async events', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('test.event', fn (EventContract $event): string => $event->getPayload());

    $future = $emitter->emitAsync('test.event', 'test data');

    $results = $future->await();

    expect($results)->toBe(['test data']);
});

it('can register and emit basic events with string-class listeners', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('test.event', StandardListener::class);

    $results = $emitter->emit('test.event', 'test data');

    expect($results)->toBe(['Event name: test.event']);
});

it('returns null for invalid listeners', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('test.event', InvalidListener::class);

    $results = $emitter->emit('test.event', 'test data');

    expect($results)->toBe([null]);
});

it('can register and emit events with facade syntax', function (): void {
    $called = false;

    EventFacade::on('facade.event', function (EventContract $event) use (&$called): void {
        $called = true;
        expect($event->getName())->toBe('facade.event');
        expect($event->getPayload())->toBe('facade data');
    });

    EventFacade::emit('facade.event', 'facade data');

    expect($called)->toBeTrue();
});

it('can register multiple listeners for same event', function (): void {
    $emitter = new EventEmitter();
    $count = 0;

    $emitter->on('multi.event', function () use (&$count): void {
        $count++;
    });

    $emitter->on('multi.event', function () use (&$count): void {
        $count++;
    });

    $emitter->emit('multi.event');

    expect($count)->toBe(2);
});

it('respects listener priorities', function (): void {
    $emitter = new EventEmitter();
    $order = [];

    $emitter->on('priority.test', function () use (&$order): void {
        $order[] = 'low';
    }, 1);

    $emitter->on('priority.test', function () use (&$order): void {
        $order[] = 'high';
    }, 10);

    $emitter->on('priority.test', function () use (&$order): void {
        $order[] = 'medium';
    }, 5);

    $emitter->emit('priority.test');

    expect($order)->toBe(['high', 'medium', 'low']);
});

it('can register one-time listeners', function (): void {
    $emitter = new EventEmitter();
    $count = 0;

    $emitter->once('once.event', function () use (&$count): void {
        $count++;
    });

    $emitter->emit('once.event');
    $emitter->emit('once.event');
    $emitter->emit('once.event');

    expect($count)->toBe(1);
});

it('can remove listeners', function (): void {
    $emitter = new EventEmitter();
    $called = false;

    $listener = function () use (&$called): void {
        $called = true;
    };

    $emitter->on('removable.event', $listener);
    $emitter->off('removable.event', $listener);
    $emitter->emit('removable.event');

    expect($called)->toBeFalse();
});

it('tries to remove non registered event', function (): void {
    $emitter = new EventEmitter();
    $called = false;

    $listener = function () use (&$called): void {
        $called = true;
    };

    $emitter->off('removable.event', $listener);

    expect($called)->toBeFalse();
});

it('can remove all listeners for an event', function (): void {
    $emitter = new EventEmitter();
    $count = 0;

    $emitter->on('clear.event', function () use (&$count): void {
        $count++;
    });

    $emitter->on('clear.event', function () use (&$count): void {
        $count++;
    });

    $emitter->off('clear.event');
    $emitter->emit('clear.event');

    expect($count)->toBe(0);
});

it('can stop event propagation', function (): void {
    $emitter = new EventEmitter();
    $count = 0;

    $emitter->on('stop.event', function (EventContract $event) use (&$count): void {
        $count++;
        $event->stopPropagation();
    });

    $emitter->on('stop.event', function (EventContract $event) use (&$count): void {
        $count++;
    });

    $emitter->emit('stop.event');

    expect($count)->toBe(1);
});

it('can stop async event propagation', function (): void {
    $emitter = new EventEmitter();
    $count = 0;

    $emitter->on('stop.event', function (EventContract $event) use (&$count): void {
        $count++;
        $event->stopPropagation();
    });

    $emitter->on('stop.event', function (EventContract $event) use (&$count): void {
        $count++;
    });

    $future = $emitter->emitAsync('stop.event');

    $future->await();

    expect($count)->toBe(1);
});

it('returns results from listeners', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('result.event', fn (): string => 'first result');

    $emitter->on('result.event', fn (): string => 'second result');

    $results = $emitter->emit('result.event');

    expect($results)->toBe(['first result', 'second result']);
});

it('can handle Event objects', function (): void {
    $emitter = new EventEmitter();
    $called = false;

    $emitter->on('custom.event', function ($event) use (&$called): void {
        $called = true;
        expect($event->getName())->toBe('custom.event');
        expect($event->getPayload())->toBe('custom data');
    });

    $event = new Event('custom.event', 'custom data');
    $emitter->emit($event);

    expect($called)->toBeTrue();
});

it('skip the listener when this should not be handled', function (): void {
    $emitter = new EventEmitter();

    $listener = $this->getMockBuilder(StandardListener::class)
        ->onlyMethods(['shouldHandle', 'handle'])
        ->getMock();

    $listener->expects($this->once())
        ->method('shouldHandle')
        ->willReturn(false);

    $listener->expects($this->never())
        ->method('handle');

    $emitter->on('custom.event', $listener);

    $emitter->emit('custom.event', 'data');
});

it('skip the listener when this should not be handled in async event', function (): void {
    $emitter = new EventEmitter();

    $listener = $this->getMockBuilder(StandardListener::class)
        ->onlyMethods(['shouldHandle', 'handle'])
        ->getMock();

    $listener->expects($this->once())
        ->method('shouldHandle')
        ->willReturn(false);

    $listener->expects($this->never())
        ->method('handle');

    $emitter->on('custom.event', $listener);

    $future = $emitter->emitAsync('custom.event', 'data');

    $future->await();
});

it('uses listener once and removes this after use', function (): void {
    $emitter = new EventEmitter();

    $listener = $this->getMockBuilder(StandardListener::class)
        ->onlyMethods(['shouldHandle', 'isOnce', 'handle'])
        ->getMock();

    $listener->expects($this->once())
        ->method('shouldHandle')
        ->willReturn(true);

    $listener->expects($this->once())
        ->method('handle')
        ->willReturn('Event name: custom.event');

    $listener->expects($this->once())
        ->method('isOnce')
        ->willReturn(true);


    $emitter->on('custom.event', $listener);

    $future = $emitter->emitAsync('custom.event', 'data');

    $future->await();
});

it('handle listener error gracefully', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('error.event', function (): never {
        throw new RuntimeError('Listener error');
    });

    Log::shouldReceive('error')
        ->once();

    $emitter->emit('error.event');
})->throws(EventException::class);

it('handle listener error gracefully in async event', function (): void {
    $emitter = new EventEmitter();
    $emitter->setEmitWarnings(true);

    $emitter->on('error.event', function (): never {
        throw new RuntimeError('Listener error');
    });

    Log::shouldReceive('error')
        ->times(2);

    $future = $emitter->emitAsync('error.event');

    $future->await();
});

it('handle listener error gracefully in async event without warnings', function (): void {
    $emitter = new EventEmitter();
    $emitter->setEmitWarnings(false);

    $emitter->on('error.event', function (): never {
        throw new RuntimeError('Listener error');
    });

    Log::shouldReceive('error')->once();

    $future = $emitter->emitAsync('error.event');

    $future->await();
});

it('can check if event has listeners', function (): void {
    $emitter = new EventEmitter();

    expect($emitter->hasListeners('nonexistent.event'))->toBeFalse();

    $emitter->on('existing.event', function (): void {
        // Do something
    });

    expect($emitter->hasListeners('existing.event'))->toBeTrue();
});

it('can get listener count', function (): void {
    $emitter = new EventEmitter();

    expect($emitter->getListenerCount('count.event'))->toBe(0);

    $emitter->on('count.event', function (): void {
        // Do something
    });
    $emitter->on('count.event', function (): void {
        // Do something
    });

    expect($emitter->getListenerCount('count.event'))->toBe(2);
});

it('can get event names', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('event.one', function (): void {
        // Do something
    });
    $emitter->on('event.two', function (): void {
        // Do something
    });

    $eventNames = $emitter->getEventNames();

    expect($eventNames)->toContain('event.one');
    expect($eventNames)->toContain('event.two');
});

it('can set max listeners', function () {
    $emitter = new EventEmitter();
    $emitter->setMaxListeners(2);

    expect($emitter->getMaxListeners())->toBe(2);
});

it('can clear all listeners', function (): void {
    $emitter = new EventEmitter();

    $emitter->on('event.one', function (): void {
        // Do something
    });
    $emitter->on('event.two', function (): void {
        // Do something
    });

    $emitter->removeAllListeners();

    expect($emitter->getEventNames())->toBeEmpty();
});

it('warns when exceeding the maximum number of listeners for an event', function (): void {
    $emitter = new EventEmitter();

    $emitter->setMaxListeners(1);
    $emitter->setEmitWarnings(true);

    Log::shouldReceive('warning')->once();

    $emitter->on('warn.event', fn (): null => null);
    $emitter->on('warn.event', fn (): null => null); // This pushes it over the limit and should log a warning

    expect($emitter->getListenerCount('warn.event'))->toBe(2);
});

it('does not warn when exceeding maximum listeners if warnings disabled', function (): void {
    $emitter = new EventEmitter();

    $emitter->setMaxListeners(1);
    $emitter->setEmitWarnings(false);

    Log::shouldReceive('warning')->never();

    $emitter->on('warn.event', fn (): null => null);
    $emitter->on('warn.event', fn (): null => null);

    expect($emitter->getListenerCount('warn.event'))->toBe(2);
});
