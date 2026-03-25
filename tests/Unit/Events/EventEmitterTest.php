<?php

declare(strict_types=1);

use Phenix\Data\Collection;
use Phenix\Events\Contracts\Event as EventContract;
use Phenix\Events\Event;
use Phenix\Events\EventEmitter;
use Phenix\Events\Exceptions\EventException;
use Phenix\Exceptions\RuntimeError;
use Phenix\Facades\Config;
use Phenix\Facades\Event as EventFacade;
use Phenix\Facades\Log;
use Tests\Unit\Events\Internal\InvalidListener;
use Tests\Unit\Events\Internal\StandardEvent;
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

it('can register and emit string-class events', function (): void {
    $emitter = new EventEmitter();

    $emitter->on(StandardEvent::class, fn (EventContract $event): string => 'string result');

    $results = $emitter->emit(StandardEvent::class, 'test data');

    expect($results)->toBe(['string result']);
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

it('can register and emit basic events and listener with custom priority', function (): void {
    $emitter = new EventEmitter();

    $listener = new StandardListener();
    $listener->setPriority(10);

    $emitter->on('test.event', $listener);

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
    expect($event->getTimestamp())->toBeFloat();
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

it('logs dispatched events while still processing listeners', function (): void {
    EventFacade::log();

    $called = false;
    EventFacade::on('logged.event', function () use (&$called): void {
        $called = true;
    });

    EventFacade::emit('logged.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::expect('logged.event')->toBeDispatched();
    EventFacade::expect('logged.event')->toBeDispatchedTimes(1);

    expect(EventFacade::getEventLog()->count())->toEqual(1);

    EventFacade::resetEventLog();

    expect(EventFacade::getEventLog()->count())->toEqual(0);

    EventFacade::emit('logged.event', 'payload-2');
    EventFacade::expect('logged.event')->toBeDispatchedTimes(1);
});

it('fakes events preventing listener execution', function (): void {
    EventFacade::fake();

    $called = false;
    EventFacade::on('fake.event', function () use (&$called): void {
        $called = true;
    });

    EventFacade::emit('fake.event', 'payload');

    expect($called)->toBeFalse();

    EventFacade::expect('fake.event')->toBeDispatched();
    EventFacade::expect('fake.event')->toBeDispatchedTimes(1);
});

it('can assert nothing dispatched', function (): void {
    EventFacade::log();

    EventFacade::expect('any.event')->toDispatchNothing();
});

it('supports closure predicate', function (): void {
    EventFacade::log();

    EventFacade::emit('closure.event', ['foo' => 'bar']);

    EventFacade::expect('closure.event')->toBeDispatched(function ($event): bool {
        return $event !== null && $event->getPayload()['foo'] === 'bar';
    });
});

it('supports closure predicate with existing event', function (): void {
    EventFacade::log();

    EventFacade::expect('neg.event')->toNotBeDispatched();
    EventFacade::expect('neg.event')->toNotBeDispatched(fn ($event): bool => false);
});

it('supports closure predicate with absent event', function (): void {
    EventFacade::log();

    EventFacade::expect('absent.event')->toNotBeDispatched();
    EventFacade::expect('absent.event')->toNotBeDispatched(fn ($event): bool => false);
});

it('fakes only specific events when a single event is provided and consumes it after first fake', function (): void {
    $calledSpecific = false;
    $calledOther = false;

    EventFacade::on('specific.event', function () use (&$calledSpecific): void {
        $calledSpecific = true; // Should NOT run because faked
    });

    EventFacade::on('other.event', function () use (&$calledOther): void {
        $calledOther = true; // Should run
    });

    EventFacade::fakeTimes('specific.event', 1);

    EventFacade::emit('specific.event', 'payload-1');

    expect($calledSpecific)->toBeFalse();

    EventFacade::expect('specific.event')->toBeDispatchedTimes(1);

    EventFacade::emit('specific.event', 'payload-2');

    expect($calledSpecific)->toBeTrue();

    EventFacade::expect('specific.event')->toBeDispatchedTimes(2);

    EventFacade::emit('other.event', 'payload');

    expect($calledOther)->toBeTrue();

    EventFacade::expect('other.event')->toBeDispatched();
});

it('supports infinite fake for single event with no times argument', function (): void {
    $called = 0;

    EventFacade::on('always.event', function () use (&$called): void {
        $called++;
    });

    EventFacade::fakeOnly('always.event');

    EventFacade::emit('always.event');
    EventFacade::emit('always.event');
    EventFacade::emit('always.event');

    expect($called)->toBe(0);

    EventFacade::expect('always.event')->toBeDispatchedTimes(3);
});

it('supports limited fake with times argument then processes listeners', function (): void {
    $called = 0;

    EventFacade::on('limited.event', function () use (&$called): void {
        $called++;
    });

    EventFacade::fakeTimes('limited.event', 2);

    EventFacade::emit('limited.event'); // fake
    EventFacade::emit('limited.event'); // fake
    EventFacade::emit('limited.event'); // real
    EventFacade::emit('limited.event'); // real

    expect($called)->toEqual(2);

    EventFacade::expect('limited.event')->toBeDispatchedTimes(4);
});

it('supports limited fake then switching to only one infinite event', function (): void {
    $limitedCalled = 0;
    $onlyCalled = 0;

    EventFacade::on('assoc.limited', function () use (&$limitedCalled): void {
        $limitedCalled++;
    });
    EventFacade::on('assoc.only', function () use (&$onlyCalled): void {
        $onlyCalled++;
    });

    EventFacade::fakeTimes('assoc.limited', 1); // fake first occurrence only

    EventFacade::emit('assoc.limited'); // fake
    EventFacade::emit('assoc.limited'); // real

    EventFacade::fakeOnly('assoc.only');

    EventFacade::emit('assoc.only'); // fake
    EventFacade::emit('assoc.only'); // fake

    EventFacade::emit('assoc.limited'); // real

    expect($limitedCalled)->toBe(2);
    expect($onlyCalled)->toBe(0);

    EventFacade::expect('assoc.limited')->toBeDispatchedTimes(3); // recorded 3 emits
    EventFacade::expect('assoc.only')->toBeDispatchedTimes(2); // recorded but never executed
});

it('supports conditional closure based faking', function (): void {
    $called = 0;

    EventFacade::log();
    EventFacade::fakeWhen('conditional.event', function (Collection $log): bool {
        $count = 0;
        foreach ($log as $entry) {
            if (($entry['name'] ?? null) === 'conditional.event') {
                $count++;
            }
        }

        return $count <= 2;
    });

    EventFacade::on('conditional.event', function () use (&$called): void {
        $called++;
    });

    EventFacade::emit('conditional.event');
    EventFacade::emit('conditional.event');
    EventFacade::emit('conditional.event');
    EventFacade::emit('conditional.event');

    expect($called)->toBe(2);

    EventFacade::expect('conditional.event')->toBeDispatchedTimes(4);
});

it('supports single event closure predicate faking', function (): void {
    $called = 0;

    EventFacade::fakeWhen('single.closure.event', function (Collection $log): bool {
        $count = 0;
        foreach ($log as $entry) {
            if (($entry['name'] ?? null) === 'single.closure.event') {
                $count++;
            }
        }

        return $count <= 2;
    });

    EventFacade::on('single.closure.event', function () use (&$called): void {
        $called++;
    });

    EventFacade::emit('single.closure.event'); // fake
    EventFacade::emit('single.closure.event'); // fake
    EventFacade::emit('single.closure.event'); // real
    EventFacade::emit('single.closure.event'); // real

    expect($called)->toBe(2);

    EventFacade::expect('single.closure.event')->toBeDispatchedTimes(4);
});

it('does not log events in production environment', function (): void {
    Config::set('app.env', 'production');

    EventFacade::log();

    EventFacade::emit('prod.logged.event', 'payload');

    expect(EventFacade::getEventLog()->count())->toEqual(0);

    Config::set('app.env', 'local');
});

it('does not fake events in production environment', function (): void {
    Config::set('app.env', 'production');

    $called = false;
    EventFacade::on('prod.fake.event', function () use (&$called): void {
        $called = true;
    });

    EventFacade::fake();

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::fakeOnly('prod.fake.event');

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::fakeWhen('prod.fake.event', function (): bool {
        return true;
    });

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::fakeTimes('prod.fake.event', 10);

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::fakeOnce('prod.fake.event');

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    EventFacade::fakeExcept('prod.fake.event');

    EventFacade::emit('prod.fake.event', 'payload');

    expect($called)->toBeTrue();

    Config::set('app.env', 'local');
});

it('fakes multiple events provided sequentially', function (): void {
    EventFacade::on('list.one', function (): never {
        throw new RuntimeError('Should not run');
    });
    EventFacade::on('list.two', function (): never {
        throw new RuntimeError('Should not run');
    });

    $executedThree = false;

    EventFacade::on('list.three', function () use (&$executedThree): void {
        $executedThree = true;
    });

    EventFacade::fakeOnly('list.one');
    EventFacade::fakeTimes('list.two', PHP_INT_MAX);

    EventFacade::emit('list.one');
    EventFacade::emit('list.one');
    EventFacade::emit('list.two');
    EventFacade::emit('list.two');

    EventFacade::emit('list.three');

    expect($executedThree)->toEqual(true);

    EventFacade::expect('list.one')->toBeDispatchedTimes(2);
    EventFacade::expect('list.two')->toBeDispatchedTimes(2);
    EventFacade::expect('list.three')->toBeDispatchedTimes(1);
});

it('ignores events configured with zero count', function (): void {
    $executed = 0;

    EventFacade::on('zero.count.event', function () use (&$executed): void {
        $executed++;
    });

    EventFacade::fakeTimes('zero.count.event', 0);

    EventFacade::emit('zero.count.event');
    EventFacade::emit('zero.count.event');

    expect($executed)->toEqual(2);

    EventFacade::expect('zero.count.event')->toBeDispatchedTimes(2);
});

it('does not fake when closure throws exception', function (): void {
    $executed = false;

    EventFacade::on('closure.exception.event', function () use (&$executed): void {
        $executed = true;
    });

    EventFacade::fakeWhen('closure.exception.event', function (Collection $log): bool {
        throw new RuntimeError('Predicate error');
    });

    EventFacade::emit('closure.exception.event');

    expect($executed)->toEqual(true);

    EventFacade::expect('closure.exception.event')->toBeDispatchedTimes(1);
});

it('fakes async emits correctly', function (): void {
    EventFacade::fake();

    $called = false;

    EventFacade::on('async.fake.event', function () use (&$called): void {
        $called = true;
    });

    $future = EventFacade::emitAsync('async.fake.event', 'payload');

    $future->await();

    expect($called)->toBeFalse();

    EventFacade::expect('async.fake.event')->toBeDispatched();
});

it('fakes once correctly', function (): void {
    $called = 0;

    EventFacade::on('fake.once.event', function () use (&$called): void {
        $called++;
    });

    EventFacade::fakeOnce('fake.once.event');

    EventFacade::emit('fake.once.event');
    EventFacade::emit('fake.once.event');
    EventFacade::emit('fake.once.event');

    expect($called)->toBe(2);

    EventFacade::expect('fake.once.event')->toBeDispatchedTimes(3);
});

it('fakes all except specified events', function (): void {
    $calledFaked = 0;
    $calledNotFaked = 0;

    EventFacade::on('not.faked.event', function () use (&$calledNotFaked): void {
        $calledNotFaked++;
    });

    EventFacade::on('faked.event', function () use (&$calledFaked): void {
        $calledFaked++;
    });

    EventFacade::fakeExcept('not.faked.event');

    EventFacade::emit('faked.event');
    EventFacade::emit('faked.event');

    EventFacade::emit('not.faked.event');
    EventFacade::emit('not.faked.event');

    expect($calledFaked)->toBe(0);
    expect($calledNotFaked)->toBe(2);

    EventFacade::expect('faked.event')->toBeDispatchedTimes(2);
    EventFacade::expect('not.faked.event')->toBeDispatchedTimes(2);
});
