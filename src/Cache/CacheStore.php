<?php

declare(strict_types=1);

namespace Phenix\Cache;

use Closure;
use Phenix\Cache\Contracts\CacheStore as CacheStoreContract;
use Phenix\Util\Date;

abstract class CacheStore implements CacheStoreContract
{
    public function remember(string $key, Date $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        $this->set($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        $this->forever($key, $value);

        return $value;
    }
}
