<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Closure;
use Mockery\Expectation;
use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Phenix\App;
use Phenix\Cache\CacheManager;
use Phenix\Cache\Constants\Store;
use Phenix\Cache\Contracts\CacheStore as CacheContract;
use Phenix\Runtime\Facade;
use Phenix\Testing\Mockery;
use Phenix\Util\Date;

/**
 * @method static CacheContract store(Store|null $storeName = null)
 * @method static mixed get(string $key, Closure|null $callback = null)
 * @method static void set(string $key, mixed $value, Date|null $ttl = null)
 * @method static void forever(string $key, mixed $value)
 * @method static mixed remember(string $key, Date $ttl, Closure $callback)
 * @method static mixed rememberForever(string $key, Closure $callback)
 * @method static bool has(string $key)
 * @method static void delete(string $key)
 * @method static void clear()
 *
 * @see \Phenix\Cache\CacheManager
 */
class Cache extends Facade
{
    public static function getKeyName(): string
    {
        return CacheManager::class;
    }

    public static function shouldReceive(string $method): Expectation|ExpectationInterface|HigherOrderMessage
    {
        $mock = Mockery::mock(self::getKeyName());

        App::fake(self::getKeyName(), $mock);

        return $mock->shouldReceive($method);
    }
}
