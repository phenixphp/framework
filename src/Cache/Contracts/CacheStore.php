<?php

declare(strict_types=1);

namespace Phenix\Cache\Contracts;

use Closure;
use Phenix\Util\Date;

interface CacheStore
{
    public function get(string $key, Closure|null $callback = null): mixed;

    public function set(string $key, mixed $value, Date|null $ttl = null): void;

    public function forever(string $key, mixed $value): void;

    public function remember(string $key, Date $ttl, Closure $callback): mixed;

    public function rememberForever(string $key, Closure $callback): mixed;

    public function has(string $key): bool;

    public function delete(string $key): void;

    public function clear(): void;
}
