<?php

declare(strict_types=1);

namespace Phenix\Scheduling;

use Phenix\Redis\Contracts\Client;
use Phenix\Scheduling\Contracts\ScheduleLock;

use function bin2hex;
use function random_bytes;

class RedisScheduleLock implements ScheduleLock
{
    public function __construct(
        protected Client $redis,
        protected string $prefix = 'phenix:schedule:'
    ) {
    }

    public function acquire(string $schedule, string $occurrence, int $ttl): string|null
    {
        $key = $this->key($schedule, $occurrence);
        $token = bin2hex(random_bytes(16));
        $script = <<<'LUA'
if redis.call('SET', KEYS[1], ARGV[2], 'EX', ARGV[1], 'NX') then
    return 1
end

return 0
LUA;

        return $this->redis->execute('EVAL', $script, 1, $key, $ttl, $token) === 1
            ? $token
            : null;
    }

    public function release(string $schedule, string $occurrence, string $token): void
    {
        $script = <<<'LUA'
if redis.call('GET', KEYS[1]) ~= ARGV[1] then
    return 0
end

return redis.call('DEL', KEYS[1])
LUA;

        $this->redis->execute('EVAL', $script, 1, $this->key($schedule, $occurrence), $token);
    }

    protected function key(string $schedule, string $occurrence): string
    {
        return $this->prefix . hash('sha256', $schedule) . ':' . $occurrence;
    }
}
