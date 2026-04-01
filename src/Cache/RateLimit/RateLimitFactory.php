<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit;

use Kelunik\RateLimit\PrefixRateLimit;
use Kelunik\RateLimit\RateLimit;
use Kelunik\RateLimit\RedisRateLimit;
use Phenix\Cache\Constants\Store;
use Phenix\Cache\Stores\LocalStore;
use Phenix\Facades\Cache;
use Phenix\Facades\Redis;

class RateLimitFactory
{
    public static function redis(int $ttl, string $connection = 'default', string $prefix = ''): RateLimit
    {
        $clientWrapper = Redis::connection($connection)->client();
        $rateLimit = new RedisRateLimit($clientWrapper->getClient(), $ttl);

        return self::withPrefix($rateLimit, $prefix);
    }

    public static function local(int $ttl): RateLimit
    {
        /** @var LocalStore $store */
        $store = Cache::store(Store::LOCAL);

        return new LocalRateLimit($store, $ttl);
    }

    public static function withPrefix(RateLimit $rateLimit, string $prefix): RateLimit
    {
        if ($prefix === '') {
            return $rateLimit;
        }

        return new PrefixRateLimit($rateLimit, $prefix);
    }
}
