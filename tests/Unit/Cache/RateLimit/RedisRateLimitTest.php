<?php

declare(strict_types=1);

use Amp\Redis\Connection\RedisLink;
use Amp\Redis\Protocol\RedisResponse;
use Amp\Redis\RedisClient;
use Kelunik\RateLimit\PrefixRateLimit;
use Kelunik\RateLimit\RedisRateLimit;
use Phenix\Cache\Constants\Store;
use Phenix\Cache\RateLimit\RateLimitManager;
use Phenix\Database\Constants\Connection;
use Phenix\Facades\Config;
use Phenix\Redis\ClientWrapper;

beforeEach(function (): void {
    Config::set('cache.default', Store::REDIS->value);
    Config::set('cache.rate_limit.store', Store::REDIS->value);
});

it('prefixes redis rate limit keys with the cache namespace', function (): void {
    Config::set('cache.prefix', 'cache-prefix:');

    $manager = new RateLimitManager();
    $limiter = $manager->limiter();

    expect($limiter)->toBeInstanceOf(PrefixRateLimit::class);

    $reflection = new ReflectionClass($limiter);

    $prefix = $reflection->getProperty('prefix');
    $prefix->setAccessible(true);

    $rateLimit = $reflection->getProperty('rateLimit');
    $rateLimit->setAccessible(true);

    expect($prefix->getValue($limiter))->toBe('cache-prefix:');
    expect($rateLimit->getValue($limiter))->toBeInstanceOf(RedisRateLimit::class);
});

it('isolates redis rate limit state across cache prefixes', function (): void {
    $incrementResponse = $this->createStub(RedisResponse::class);
    $incrementResponse->method('unwrap')->willReturn(1);

    $expireResponse = $this->createStub(RedisResponse::class);
    $expireResponse->method('unwrap')->willReturn(1);

    $getResponse = $this->createStub(RedisResponse::class);
    $getResponse->method('unwrap')->willReturn(null);

    $link = $this->createMock(RedisLink::class);

    $link->expects($this->exactly(3))
        ->method('execute')
        ->withConsecutive(
            [
                $this->equalTo('incr'),
                $this->equalTo(['first-prefix:route:client']),
            ],
            [
                $this->equalTo('expire'),
                $this->equalTo(['first-prefix:route:client', 60]),
            ],
            [
                $this->equalTo('get'),
                $this->equalTo(['second-prefix:route:client']),
            ]
        )
        ->willReturnOnConsecutiveCalls($incrementResponse, $expireResponse, $getResponse);

    $client = new RedisClient($link);
    $this->app->swap(Connection::redis('default'), new ClientWrapper($client));

    Config::set('cache.prefix', 'first-prefix:');
    (new RateLimitManager())->prefixed('route:')->increment('client');

    Config::set('cache.prefix', 'second-prefix:');

    expect((new RateLimitManager())->prefixed('route:')->get('client'))->toBe(0);
});
