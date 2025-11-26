<?php

declare(strict_types=1);

use Phenix\Facades\Cache;
use Phenix\Util\Date;

use function Amp\delay;

it('stores and retrieves a value', function (): void {
    Cache::set('test_key', 'test_value');

    $value = Cache::get('test_key');

    expect($value)->toBe('test_value');
    expect(Cache::has('test_key'))->toBeTrue();
});

it('stores value with custom ttl', function (): void {
    Cache::set('temp_key', 'temp_value', Date::now()->addSeconds(2));

    expect(Cache::has('temp_key'))->toBeTrue();

    delay(3);

    expect(Cache::has('temp_key'))->toBeFalse();
});

it('computes value via callback when missing', function (): void {
    $value = Cache::get('missing', static fn (): string => 'generated');

    expect($value)->toBe('generated');
});

it('removes value correctly', function (): void {
    Cache::set('to_be_deleted', 'value');

    expect(Cache::has('to_be_deleted'))->toBeTrue();

    Cache::delete('to_be_deleted');

    expect(Cache::has('to_be_deleted'))->toBeFalse();
});

it('remembers value when cache is empty', function (): void {
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
    Cache::set('remember_key', 'cached_value', Date::now()->addMinutes(5));

    $callCount = 0;

    $value = Cache::remember('remember_key', Date::now()->addMinutes(5), function () use (&$callCount): string {
        $callCount++;

        return 'computed_value';
    });

    expect($value)->toBe('cached_value');
    expect($callCount)->toBe(0);
});

it('remembers forever when cache is empty', function (): void {
    $callCount = 0;

    $value = Cache::rememberForever('forever_key', function () use (&$callCount): string {
        $callCount++;

        return 'forever_value';
    });

    expect($value)->toBe('forever_value');
    expect($callCount)->toBe(1);
    expect(Cache::has('forever_key'))->toBeTrue();

    delay(0.5);

    expect(Cache::get('forever_key'))->toBe('forever_value');
});

it('remembers forever when cache exists', function (): void {
    Cache::forever('forever_key', 'existing_value');

    $callCount = 0;

    $value = Cache::rememberForever('forever_key', function () use (&$callCount): string {
        $callCount++;

        return 'new_value';
    });

    expect($value)->toBe('existing_value');
    expect($callCount)->toBe(0);
});
