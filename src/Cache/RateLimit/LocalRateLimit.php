<?php

declare(strict_types=1);

namespace Phenix\Cache\RateLimit;

use Kelunik\RateLimit\RateLimit;
use Phenix\Cache\Stores\LocalStore;
use Phenix\Util\Date;

class LocalRateLimit implements RateLimit
{
    private int $ttl;

    private LocalStore $store;

    public function __construct(LocalStore $store, int $ttl)
    {
        $this->store = $store;
        $this->ttl = $ttl;
    }

    public function get(string $id): int
    {
        $data = $this->store->get($id);

        if ($data === null) {
            return 0;
        }

        return (int) ($data['count'] ?? 0);
    }

    public function increment(string $id): int
    {
        $currentTime = time();
        $data = $this->store->get($id);

        if ($data === null) {
            $data = [
                'count' => 1,
                'expires_at' => $currentTime + $this->ttl,
            ];

            $this->store->set($id, $data, Date::now()->addSeconds($this->ttl));

            return 1;
        }

        $data['count'] = ((int) ($data['count'] ?? 0)) + 1;

        if (! isset($data['expires_at'])) {
            $data['expires_at'] = $currentTime + $this->ttl;
        }

        $remainingTtl = max(0, ((int) $data['expires_at']) - $currentTime);
        $this->store->set($id, $data, Date::now()->addSeconds($remainingTtl));

        return (int) $data['count'];
    }

    public function getTtl(string $id): int
    {
        $data = $this->store->get($id);

        if ($data === null || ! isset($data['expires_at'])) {
            return $this->ttl;
        }

        $ttl = ((int) $data['expires_at']) - time();

        return max(0, $ttl);
    }

    public function clear(): void
    {
        $this->store->clear();
    }
}
