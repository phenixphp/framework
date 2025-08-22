<?php

declare(strict_types=1);

namespace Phenix\Queue;

class LuaScripts
{
    public static function pop(): string
    {
        return <<<'LUA'
-- KEYS[1] - queue key (e.g., "queues:default")
-- KEYS[2] - failed tasks set key (e.g., "queues:failed")
-- KEYS[3] - delayed queue key (e.g., "queues:delayed")
-- ARGV[1] - current timestamp
-- ARGV[2] - reservation timeout (seconds)

-- First, move any delayed tasks that are ready back to the main queue
local current_time = tonumber(ARGV[1])
local ready_tasks = redis.call('ZRANGEBYSCORE', KEYS[3], 0, current_time)

if #ready_tasks > 0 then
    -- Move ready tasks from delayed queue to main queue
    for i = 1, #ready_tasks do
        redis.call('LPUSH', KEYS[1], ready_tasks[i])
    end
    -- Remove the tasks from delayed queue
    redis.call('ZREMRANGEBYSCORE', KEYS[3], 0, current_time)
end

local function pop_next_task()
    local payload = redis.call('LPOP', KEYS[1])
    if not payload then
        return nil
    end

    -- Deserialize to get task ID (we need to extract it from the payload)
    -- Since we can't unserialize in Lua, we'll use a pattern to extract task ID
    local task_id = string.match(payload, '"taskId";s:%d+:"([^"]+)"')
    if not task_id then
        -- If we can't extract task ID, assume it's not failed and return it
        return payload
    end

    -- Check if task is in failed set
    local failed_key = "task:failed:" .. task_id
    if redis.call('EXISTS', failed_key) == 1 then
        -- Task has failed, skip it and try next
        return nil
    end

    return payload
end

-- Try to pop a valid task (not failed)
local max_attempts = 10
local attempts = 0

while attempts < max_attempts do
    local payload = pop_next_task()
    if payload then
        return payload
    end
    attempts = attempts + 1
end

-- No valid task found after max attempts
return nil
LUA;
    }

    public static function reserve(): string
    {
        return <<<'LUA'
-- KEYS[1] - reserved key (e.g., "task:reserved:task_id")
-- KEYS[2] - task data key (e.g., "task:data:task_id")
-- ARGV[1] - expiration timestamp
-- ARGV[2] - attempts count
-- ARGV[3] - reserved_at timestamp
-- ARGV[4] - reserved_until timestamp
-- ARGV[5] - payload
-- ARGV[6] - expire_in_seconds

-- Try to set reservation (atomic)
if redis.call('SETNX', KEYS[1], ARGV[1]) == 1 then
    -- Successfully reserved, now set task data
    redis.call('HSET', KEYS[2],
        'attempts', ARGV[2],
        'reserved_at', ARGV[3],
        'reserved_until', ARGV[4],
        'payload', ARGV[5]
    )

    -- Set expiration for task data
    redis.call('EXPIRE', KEYS[2], ARGV[6])

    return 1
else
    return 0
end
LUA;
    }

    public static function push(): string
    {
        return <<<'LUA'
-- KEYS[1] - queue key (e.g., "queues:default")
-- ARGV[1] - serialized task payload

return redis.call('RPUSH', KEYS[1], ARGV[1])
LUA;
    }

    public static function retry(): string
    {
        return <<<'LUA'
-- KEYS[1] - reserved key (e.g., "task:reserved:task_id")
-- KEYS[2] - task data key (e.g., "task:data:task_id")
-- KEYS[3] - queue key (e.g., "queues:default")
-- KEYS[4] - delayed queue key (e.g., "queues:delayed")
-- ARGV[1] - attempts count
-- ARGV[2] - payload
-- ARGV[3] - delay (0 for immediate, >0 for delayed)
-- ARGV[4] - execute_at timestamp (only used if delay > 0)

-- Remove reservation
redis.call('DEL', KEYS[1])

-- Update task data with new attempt count
redis.call('HSET', KEYS[2], 'attempts', ARGV[1])

if tonumber(ARGV[3]) > 0 then
    -- Add to delayed queue with score = execute_at timestamp
    redis.call('ZADD', KEYS[4], ARGV[4], ARGV[2])
else
    -- Add back to main queue immediately
    redis.call('RPUSH', KEYS[3], ARGV[2])
end

return 1
LUA;
    }

    public static function fail(): string
    {
        return <<<'LUA'
-- KEYS[1] - reserved key (e.g., "task:reserved:task_id")
-- KEYS[2] - task data key (e.g., "task:data:task_id")
-- KEYS[3] - failed key (e.g., "task:failed:task_id")
-- KEYS[4] - failed queue (e.g., "queues:failed")
-- ARGV[1] - task_id
-- ARGV[2] - failed_at timestamp
-- ARGV[3] - exception data (JSON)
-- ARGV[4] - payload

-- Create failed task record
redis.call('HSET', KEYS[3],
    'task_id', ARGV[1],
    'failed_at', ARGV[2],
    'exception', ARGV[3],
    'payload', ARGV[4]
)

-- Add task ID to failed queue for management
redis.call('LPUSH', KEYS[4], ARGV[1])

-- Clean up reservation and task data
redis.call('DEL', KEYS[1], KEYS[2])

return 1
LUA;
    }

    public static function cleanupExpiredReservations(): string
    {
        return <<<'LUA'
-- ARGV[1] - current timestamp

local cursor = 0
local cleaned_count = 0
local batch_size = 100

repeat
    local result = redis.call("SCAN", cursor, "MATCH", "task:reserved:*", "COUNT", batch_size)
    cursor = tonumber(result[1])
    local reserved_keys = result[2]

    for i = 1, #reserved_keys do
        local key = reserved_keys[i]
        local expiration = redis.call("GET", key)

        if expiration and tonumber(expiration) < tonumber(ARGV[1]) then
            redis.call("DEL", key)
            cleaned_count = cleaned_count + 1

            -- Extract task ID from key and update task data
            local task_id = string.match(key, "task:reserved:(.+)")
            if task_id then
                local task_data_key = "task:data:" .. task_id
                redis.call("HDEL", task_data_key, "reserved_at")
                redis.call("HSET", task_data_key, "available_at", ARGV[1])
            end
        end
    end
until cursor == 0

return cleaned_count
LUA;
    }
}
