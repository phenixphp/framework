<?php

declare(strict_types=1);

use Phenix\Cache\Constants\Store;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Cache;
use Phenix\Facades\Config;
use Phenix\Redis\ClientWrapper;
use Phenix\Util\Date;

use function Amp\delay;

beforeEach(function (): void {
    Config::set('cache.default', Store::REDIS->value);

    $this->prefix = Config::get('cache.prefix');
});

it('stores and retrieves a value', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('SETEX'),
                $this->equalTo("{$this->prefix}test_key"),
                $this->isType('int'),
                $this->equalTo('test_value'),
            ],
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}test_key"),
            ],
            [
                $this->equalTo('EXISTS'),
                $this->equalTo("{$this->prefix}test_key"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            'test_value',
            1
        );

    $this->app->swap(Connection::redis('default'), $client);

    Cache::set('test_key', 'test_value');

    $value = Cache::get('test_key');

    expect($value)->toBe('test_value');
    expect(Cache::has('test_key'))->toBeTrue();
});

it('computes value via callback on miss', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(2))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}beta"),
            ],
            [
                $this->equalTo('SETEX'),
                $this->equalTo("{$this->prefix}beta"),
                $this->isType('int'),
                $this->equalTo('generated'),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            null
        );

    $this->app->swap(Connection::redis('default'), $client);

    $value = Cache::get('beta', static fn (): string => 'generated');

    expect($value)->toBe('generated');
});

it('expires values using ttl', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('SETEX'),
                $this->equalTo("{$this->prefix}temp"),
                $this->callback(function (int $ttl): bool {
                    return $ttl >= 0 && $ttl <= 2;
                }),
                $this->equalTo('soon-gone'),
            ],
            [
                $this->equalTo('EXISTS'),
                $this->equalTo("{$this->prefix}temp"),
            ],
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}temp"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            0,
            null
        );

    $this->app->swap(Connection::redis('default'), $client);

    Cache::set('temp', 'soon-gone', Date::now()->addSeconds(1));

    delay(2);

    expect(Cache::has('temp'))->toBeFalse();
    expect(Cache::get('temp'))->toBeNull();
});

it('deletes single value', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('SETEX'),
                $this->equalTo("{$this->prefix}gamma"),
                $this->isType('int'),
                $this->equalTo(42),
            ],
            [
                $this->equalTo('DEL'),
                $this->equalTo("{$this->prefix}gamma"),
            ],
            [
                $this->equalTo('EXISTS'),
                $this->equalTo("{$this->prefix}gamma"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            1,
            0
        );

    $this->app->swap(Connection::redis('default'), $client);

    Cache::set('gamma', 42);
    Cache::delete('gamma');

    expect(Cache::has('gamma'))->toBeFalse();
});

it('clears all values', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $prefix = $this->prefix;

    $client->expects($this->exactly(5))
        ->method('execute')
        ->willReturnCallback(function (...$args) use ($prefix) {
            static $callCount = 0;
            $callCount++;

            if ($callCount === 1 || $callCount === 2) {
                return null;
            }

            if ($callCount === 3) {
                expect($args[0])->toBe('SCAN');
                expect($args[1])->toBe(0);
                expect($args[2])->toBe('MATCH');
                expect($args[3])->toBe("{$prefix}*");
                expect($args[4])->toBe('COUNT');
                expect($args[5])->toBe(1000);

                return [["{$prefix}a", "{$prefix}b"], '0'];
            }

            if ($callCount === 4) {
                expect($args[0])->toBe('DEL');
                expect($args[1])->toBe("{$prefix}a");
                expect($args[2])->toBe("{$prefix}b");

                return 2;
            }

            if ($callCount === 5) {
                return 0;
            }

            return null;
        });

    $this->app->swap(Connection::redis('default'), $client);

    Cache::set('a', 1);
    Cache::set('b', 2);

    Cache::clear();

    expect(Cache::has('a'))->toBeFalse();
});

it('stores forever without expiration', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(2))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('SET'),
                $this->equalTo("{$this->prefix}perm"),
                $this->equalTo('always'),
            ],
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}perm"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            'always'
        );

    $this->app->swap(Connection::redis('default'), $client);

    Cache::forever('perm', 'always');

    delay(0.5);

    expect(Cache::get('perm'))->toBe('always');
});

it('stores with default ttl', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('SETEX'),
            $this->equalTo("{$this->prefix}delta"),
            $this->callback(function (int $ttl): bool {
                return $ttl >= 3550 && $ttl <= 3650;
            }),
            $this->equalTo('value')
        )
        ->willReturn(null);

    $this->app->swap(Connection::redis('default'), $client);

    Cache::set('delta', 'value');
});

it('mocks cache facade methods', function (): void {
    Cache::shouldReceive('get')
        ->once()
        ->with('mocked_key')
        ->andReturn('mocked_value');

    $value = Cache::get('mocked_key');

    expect($value)->toBe('mocked_value');
});

it('remembers value when cache is empty', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}remember_key"),
            ],
            [
                $this->equalTo('SETEX'),
                $this->equalTo("{$this->prefix}remember_key"),
                $this->isType('int'),
                $this->equalTo('computed_value'),
            ],
            [
                $this->equalTo('EXISTS'),
                $this->equalTo("{$this->prefix}remember_key"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            null,
            1
        );

    $this->app->swap(Connection::redis('default'), $client);

    $callCount = 0;

    $value = Cache::remember('remember_key', Date::now()->addMinutes(5), function () use (&$callCount): string {
        $callCount++;

        return 'computed_value';
    });

    expect($value)->toBe('computed_value');
    expect($callCount)->toBe(1);
    expect(Cache::has('remember_key'))->toBeTrue();
});

it('remembers value when cache exists', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('GET'),
            $this->equalTo("{$this->prefix}remember_key")
        )
        ->willReturn('cached_value');

    $this->app->swap(Connection::redis('default'), $client);

    $callCount = 0;

    $value = Cache::remember('remember_key', Date::now()->addMinutes(5), function () use (&$callCount): string {
        $callCount++;

        return 'computed_value';
    });

    expect($value)->toBe('cached_value');
    expect($callCount)->toBe(0);
});

it('remembers forever when cache is empty', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('GET'),
                $this->equalTo("{$this->prefix}forever_key"),
            ],
            [
                $this->equalTo('SET'),
                $this->equalTo("{$this->prefix}forever_key"),
                $this->equalTo('forever_value'),
            ],
            [
                $this->equalTo('EXISTS'),
                $this->equalTo("{$this->prefix}forever_key"),
            ]
        )
        ->willReturnOnConsecutiveCalls(
            null,
            null,
            1
        );

    $this->app->swap(Connection::redis('default'), $client);

    $callCount = 0;

    $value = Cache::rememberForever('forever_key', function () use (&$callCount): string {
        $callCount++;

        return 'forever_value';
    });

    expect($value)->toBe('forever_value');
    expect($callCount)->toBe(1);
    expect(Cache::has('forever_key'))->toBeTrue();
});

it('remembers forever when cache exists', function (): void {
    $client = $this->getMockBuilder(ClientWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $client->expects($this->once())
        ->method('execute')
        ->with(
            $this->equalTo('GET'),
            $this->equalTo("{$this->prefix}forever_key")
        )
        ->willReturn('existing_value');

    $this->app->swap(Connection::redis('default'), $client);

    $callCount = 0;

    $value = Cache::rememberForever('forever_key', function () use (&$callCount): string {
        $callCount++;

        return 'new_value';
    });

    expect($value)->toBe('existing_value');
    expect($callCount)->toBe(0);
});
