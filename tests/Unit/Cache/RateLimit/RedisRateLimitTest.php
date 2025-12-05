<?php

declare(strict_types=1);

use Kelunik\RateLimit\RedisRateLimit;
use Phenix\Cache\Constants\Store;
use Phenix\Cache\RateLimit\RateLimitManager;
use Phenix\Facades\Config;

beforeEach(function (): void {
    Config::set('cache.default', Store::REDIS->value);
    Config::set('cache.rate_limit.store', Store::REDIS->value);
});

it('call redis rate limit factory', function (): void {
    $manager = new RateLimitManager();

    expect($manager->limiter())->toBeInstanceOf(RedisRateLimit::class);
});
