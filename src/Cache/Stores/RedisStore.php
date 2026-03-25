<?php

declare(strict_types=1);

namespace Phenix\Cache\Stores;

use Closure;
use Phenix\Cache\CacheStore;
use Phenix\Redis\Contracts\Client;
use Phenix\Util\Date;

class RedisStore extends CacheStore
{
    public function __construct(
        protected Client $client,
        protected string $prefix = '',
        protected int $ttl = 60
    ) {
    }

    public function get(string $key, Closure|null $callback = null): mixed
    {
        $value = $this->client->execute('GET', $this->getPrefixedKey($key));

        if ($value === null && $callback !== null) {
            $value = $callback();

            $this->set($key, $value);
        }

        return $value;
    }

    public function set(string $key, mixed $value, Date|null $ttl = null): void
    {
        $ttl ??= Date::now()->addMinutes($this->ttl);
        $seconds = Date::now()->diffInSeconds($ttl);

        $this->client->execute('SETEX', $this->getPrefixedKey($key), (int) $seconds, $value);
    }

    public function forever(string $key, mixed $value): void
    {
        $this->client->execute('SET', $this->getPrefixedKey($key), $value);
    }

    public function has(string $key): bool
    {
        return $this->client->execute('EXISTS', $this->getPrefixedKey($key)) === 1;
    }

    public function delete(string $key): void
    {
        $this->client->execute('DEL', $this->getPrefixedKey($key));
    }

    public function clear(): void
    {
        $iterator = null;

        do {
            [$keys, $iterator] = $this->client->execute('SCAN', $iterator ?? 0, 'MATCH', $this->getPrefixedKey('*'), 'COUNT', 1000);

            if (! empty($keys)) {
                $this->client->execute('DEL', ...$keys);
            }
        } while ($iterator !== '0');
    }

    protected function getPrefixedKey(string $key): string
    {
        return "{$this->prefix}{$key}";
    }
}
