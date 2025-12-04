<?php

declare(strict_types=1);

use Amp\Cache\LocalCache;
use Phenix\Cache\RateLimit\LocalRateLimit;
use Phenix\Cache\Stores\LocalStore;

it('can increment rate limit counter', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 60);

    expect($rateLimit->get('test'))->toBe(0);
    expect($rateLimit->increment('test'))->toBe(1);
    expect($rateLimit->increment('test'))->toBe(2);
    expect($rateLimit->get('test'))->toBe(2);
});

it('can get time to live for rate limit', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 60);

    $rateLimit->increment('test');

    $ttl = $rateLimit->getTtl('test');

    expect($ttl)->toBeGreaterThan(50);
    expect($ttl)->toBeLessThanOrEqual(60);
});

it('cleans up expired entries', function (): void {
    $store = new LocalStore(new LocalCache());
    $rateLimit = new LocalRateLimit($store, 1); // 1 second TTL

    $rateLimit->increment('test');
    expect($rateLimit->get('test'))->toBe(1);

    sleep(2); // Wait for expiration

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
