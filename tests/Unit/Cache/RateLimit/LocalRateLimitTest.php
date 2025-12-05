<?php

declare(strict_types=1);

use Amp\Cache\LocalCache;
use Phenix\Cache\RateLimit\LocalRateLimit;
use Phenix\Cache\Stores\LocalStore;
use Phenix\Util\Date;

use function Amp\delay;

it('can increment rate limit counter', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 60);

    expect($rateLimit->get('test'))->toBe(0);
    expect($rateLimit->increment('test'))->toBe(1);
    expect($rateLimit->increment('test'))->toBe(2);
    expect($rateLimit->get('test'))->toBe(2);
});

it('sets expires_at when missing on existing entry', function (): void {
    $store = $this->getMockBuilder(LocalStore::class)
        ->disableOriginalConstructor()
        ->getMock();

    $store->expects($this->once())
        ->method('get')
        ->with('user:1')
        ->willReturn(['count' => 0]);

    $store->expects($this->once())
        ->method('set')
        ->with(
            'user:1',
            $this->callback(function (array $data): bool {
                return isset($data['expires_at']) && (int) ($data['count'] ?? 0) === 1;
            }),
            $this->isInstanceOf(Date::class)
        );

    $rateLimit = new LocalRateLimit($store, 60);

    $count = $rateLimit->increment('user:1');

    expect($count)->toBe(1);
});

it('can get time to live for rate limit', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 60);

    $rateLimit->increment('test');

    $ttl = $rateLimit->getTtl('test');

    expect($ttl)->toBeGreaterThan(50);
    expect($ttl)->toBeLessThanOrEqual(60);
});

it('returns default ttl when expires_at missing', function (): void {
    $store = $this->getMockBuilder(LocalStore::class)
        ->disableOriginalConstructor()
        ->getMock();

    $store->expects($this->once())
        ->method('get')
        ->with('user:2')
        ->willReturn(['count' => 1]);

    $rateLimit = new LocalRateLimit($store, 60);

    $ttl = $rateLimit->getTtl('user:2');

    expect($ttl)->toBe(60);
});

it('cleans up expired entries', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 1); // 1 second TTL

    $rateLimit->increment('test');
    expect($rateLimit->get('test'))->toBe(1);

    delay(2); // Wait for expiration

    expect($rateLimit->get('test'))->toBe(0);
});

it('can reset rate limit entries', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 60);

    $rateLimit->increment('test1');
    $rateLimit->increment('test2');

    expect($rateLimit->get('test1'))->toBe(1);
    expect($rateLimit->get('test2'))->toBe(1);

    $rateLimit->clear();

    expect($rateLimit->get('test1'))->toBe(0);
    expect($rateLimit->get('test2'))->toBe(0);
});
