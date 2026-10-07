<?php

declare(strict_types=1);

namespace Phenix\Queue;

final class LuaScripts
{
    public static function push(): string
    {
        return <<<'LUA'
if redis.call('EXISTS', KEYS[2]) == 1 then
    return 0
end

redis.call('HSET', KEYS[2],
    'payload', ARGV[2],
    'queue_name', ARGV[3],
    'attempts', 0,
    'created_at', ARGV[4]
)
redis.call('RPUSH', KEYS[1], ARGV[1])

return 1
LUA;
    }

    public static function pop(): string
    {
        return <<<'LUA'
local now = tonumber(ARGV[1])
local reserved_until = tonumber(ARGV[2])
local receipt = ARGV[3]

local expired = redis.call('ZRANGEBYSCORE', KEYS[2], 0, now, 'LIMIT', 0, 100)
for _, task_id in ipairs(expired) do
    redis.call('ZREM', KEYS[2], task_id)
    redis.call('RPUSH', KEYS[1], task_id)
    redis.call('HDEL', 'task:data:' .. task_id, 'reserved_at', 'reserved_until', 'receipt')
end

local delayed = redis.call('ZRANGEBYSCORE', KEYS[3], 0, now, 'LIMIT', 0, 100)
for _, task_id in ipairs(delayed) do
    redis.call('ZREM', KEYS[3], task_id)
    redis.call('RPUSH', KEYS[1], task_id)
end

while true do
    local task_id = redis.call('LPOP', KEYS[1])
    if not task_id then
        return nil
    end

    local data_key = 'task:data:' .. task_id
    local payload = redis.call('HGET', data_key, 'payload')
    if payload then
        local attempts = redis.call('HINCRBY', data_key, 'attempts', 1)
        redis.call('HSET', data_key, 'reserved_at', now, 'reserved_until', reserved_until, 'receipt', receipt)
        redis.call('ZADD', KEYS[2], reserved_until, task_id)
        return {task_id, payload, attempts}
    end
end
LUA;
    }

    public static function complete(): string
    {
        return <<<'LUA'
if redis.call('HGET', KEYS[2], 'receipt') ~= ARGV[2] then
    return 0
end

redis.call('ZREM', KEYS[1], ARGV[1])
redis.call('DEL', KEYS[2])
return 1
LUA;
    }

    public static function retry(): string
    {
        return <<<'LUA'
if redis.call('HGET', KEYS[2], 'receipt') ~= ARGV[5] then
    return 0
end

redis.call('ZREM', KEYS[1], ARGV[1])
redis.call('HSET', KEYS[2], 'payload', ARGV[2])
redis.call('HDEL', KEYS[2], 'reserved_at', 'reserved_until', 'receipt')

if tonumber(ARGV[3]) > 0 then
    redis.call('ZADD', KEYS[4], ARGV[4], ARGV[1])
else
    redis.call('RPUSH', KEYS[3], ARGV[1])
end

redis.call('DEL', KEYS[5])
redis.call('LREM', KEYS[6], 0, ARGV[1])
return 1
LUA;
    }

    public static function fail(): string
    {
        return <<<'LUA'
if redis.call('HGET', KEYS[2], 'receipt') ~= ARGV[6] then
    return 0
end

redis.call('ZREM', KEYS[1], ARGV[1])
redis.call('HSET', KEYS[3],
    'task_id', ARGV[1],
    'failed_at', ARGV[2],
    'exception', ARGV[3],
    'payload', ARGV[4],
    'queue_name', ARGV[5]
)
redis.call('LPUSH', KEYS[4], ARGV[1])
redis.call('DEL', KEYS[2])
return 1
LUA;
    }

    public static function clear(): string
    {
        return <<<'LUA'
local ids = redis.call('LRANGE', KEYS[1], 0, -1)
local delayed = redis.call('ZRANGE', KEYS[3], 0, -1)

for _, task_id in ipairs(ids) do
    if not redis.call('ZSCORE', KEYS[2], task_id) then
        redis.call('DEL', 'task:data:' .. task_id)
    end
end

for _, task_id in ipairs(delayed) do
    if not redis.call('ZSCORE', KEYS[2], task_id) then
        redis.call('DEL', 'task:data:' .. task_id)
    end
end

redis.call('DEL', KEYS[1], KEYS[3])
return #ids + #delayed
LUA;
    }
}
