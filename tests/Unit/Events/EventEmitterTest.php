<?php

declare(strict_types=1);

use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Events\Event;
use Phenix\Events\EventEmitter;
use Phenix\Facades\Event as EventFacade;

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
