<?php

declare(strict_types=1);

use Phenix\Cache\Constants\Store;
use Phenix\Facades\Cache;
use Phenix\Facades\Config;
use Phenix\Facades\File;
use Phenix\Util\Date;

use function Amp\delay;

beforeEach(function (): void {
    Config::set('cache.default', Store::FILE->value);

    Cache::clear();
});

it('stores and retrieves a value', function (): void {
    Cache::set('alpha', ['x' => 1]);

    expect(Cache::has('alpha'))->toBeTrue();
    expect(Cache::get('alpha'))->toEqual(['x' => 1]);
});

it('computes value via callback on miss', function (): void {
    $value = Cache::get('beta', static fn (): string => 'generated');

    expect($value)->toBe('generated');
    expect(Cache::has('beta'))->toBeTrue();
});

it('expires values using ttl', function (): void {
    Cache::set('temp', 'soon-gone', Date::now()->addSeconds(1));

    delay(2);

    expect(Cache::has('temp'))->toBeFalse();
    expect(Cache::get('temp'))->toBeNull();
});

it('deletes single value', function (): void {
    Cache::set('gamma', 42);
    Cache::delete('gamma');

    expect(Cache::has('gamma'))->toBeFalse();
});

it('clears all values', function (): void {
    Cache::set('a', 1);
    Cache::set('b', 2);

    Cache::clear();

    expect(Cache::has('a'))->toBeFalse();
    expect(Cache::has('b'))->toBeFalse();
});

it('stores forever without expiration', function (): void {
    Cache::forever('perm', 'always');

    delay(0.5);

    expect(Cache::get('perm'))->toBe('always');
});

it('stores with default ttl roughly one hour', function (): void {
    Cache::set('delta', 'value');

    $files = glob(Config::get('cache.stores.file.path') . '/*.cache');
    $file = $files[0] ?? null;

    expect($file)->not()->toBeNull();

    $data = json_decode(file_get_contents($file), true);

    expect($data['expires_at'])->toBeGreaterThan(time() + 3500);
    expect($data['expires_at'])->toBeLessThan(time() + 3700);
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

it('tries to get expired cache and callback', function (): void {
    Cache::set('short_lived', 'to_expire', Date::now()->addSeconds(1));

    delay(2);

    $callCount = 0;

    $value = Cache::get('short_lived', function () use (&$callCount): string {
        $callCount++;

        return 'refreshed_value';
    });

    expect($value)->toBe('refreshed_value');
    expect($callCount)->toBe(1);
});

it('handles corrupted cache file gracefully', function (): void {
    $cachePath = Config::get('cache.stores.file.path');
    $prefix = Config::get('cache.prefix');

    $filename = $cachePath . DIRECTORY_SEPARATOR . sha1("{$prefix}corrupted") . '.cache';

    File::put($filename, 'not a valid json');

    $callCount = 0;

    $value = Cache::get('corrupted', function () use (&$callCount): string {
        $callCount++;

        return 'fixed_value';
    });

    expect($value)->toBe('fixed_value');
    expect($callCount)->toBe(1);
    expect(Cache::has('corrupted'))->toBeTrue();
});

it('rejects unsigned serialized cache payloads', function (): void {
    $cachePath = Config::get('cache.stores.file.path');
    $prefix = Config::get('cache.prefix');

    $filename = $cachePath . DIRECTORY_SEPARATOR . sha1("{$prefix}unsigned") . '.cache';

    File::put($filename, json_encode([
        'expires_at' => time() + 3600,
        'value' => base64_encode(serialize(new stdClass())),
    ], JSON_THROW_ON_ERROR));

    $callCount = 0;

    $value = Cache::get('unsigned', function () use (&$callCount): string {
        $callCount++;

        return 'safe_value';
    });

    expect($value)->toBe('safe_value');
    expect($callCount)->toBe(1);
});

it('handles corrupted trying to check cache exists', function (): void {
    $cachePath = Config::get('cache.stores.file.path');
    $prefix = Config::get('cache.prefix');

    $filename = $cachePath . DIRECTORY_SEPARATOR . sha1("{$prefix}corrupted") . '.cache';

    File::put($filename, 'not a valid json');

    expect(Cache::has('corrupted'))->toBeFalse();
});
