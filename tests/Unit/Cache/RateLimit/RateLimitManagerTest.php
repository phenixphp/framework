<?php

declare(strict_types=1);

use Phenix\Cache\RateLimit\RateLimitManager;

it('uses memory driver and increments', function (): void {
    $manager = new RateLimitManager();

    expect($manager->get('unit:test'))->toBe(0);
    expect($manager->increment('unit:test'))->toBe(1);
    expect($manager->get('unit:test'))->toBe(1);
    expect($manager->getTtl('unit:test'))->toBeGreaterThan(0);
});

it('can apply prefix to keys', function (): void {
    $manager = (new RateLimitManager())->prefixed('api:');

    $manager->increment('users');

    $plain = new RateLimitManager();

    expect($plain->get('users'))->toBe(0);
});
