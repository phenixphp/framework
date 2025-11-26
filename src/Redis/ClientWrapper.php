<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Amp\Redis\Command\Option\SetOptions;
use Amp\Redis\Command\RedisHyperLogLog;
use Amp\Redis\Command\RedisList;
use Amp\Redis\Command\RedisMap;
use Amp\Redis\Command\RedisSet;
use Amp\Redis\Command\RedisSortedSet;
use Amp\Redis\RedisClient;
use Phenix\Redis\Contracts\Client as ClientContract;
use Traversable;

/**
 * Redis client wrapper that delegates all method calls to the underlying Amphp RedisClient.
 *
 * @method RedisHyperLogLog getHyperLogLog(string $key)
 * @method RedisList getList(string $key)
 * @method RedisMap getMap(string $key)
 * @method RedisSet getSet(string $key)
 * @method RedisSortedSet getSortedSet(string $key)
 * @method int delete(string $key, string ...$keys)
 * @method string dump(string $key)
 * @method bool has(string $key)
 * @method bool expireIn(string $key, int $seconds)
 * @method bool expireInMillis(string $key, int $millis)
 * @method bool expireAt(string $key, int $timestamp)
 * @method bool expireAtMillis(string $key, int $timestamp)
 * @method array<string> getKeys(string $pattern = '*')
 * @method bool move(string $key, int $db)
 * @method int getObjectRefcount(string $key)
 * @method string getObjectEncoding(string $key)
 * @method int getObjectIdletime(string $key)
 * @method bool persist(string $key)
 * @method string|null getRandomKey()
 * @method void rename(string $key, string $newKey)
 * @method void renameWithoutOverwrite(string $key, string $newKey)
 * @method void restore(string $key, string $serializedValue, int $ttl = 0)
 * @method Traversable<string> scan(string|null $pattern = null, int|null $count = null)
 * @method int getTtl(string $key)
 * @method int getTtlInMillis(string $key)
 * @method string getType(string $key)
 * @method int append(string $key, string $value)
 * @method int countBits(string $key, int|null $start = null, int|null $end = null)
 * @method int storeBitwiseAnd(string $destination, string $key, string ...$keys)
 * @method int storeBitwiseOr(string $destination, string $key, string ...$keys)
 * @method int storeBitwiseXor(string $destination, string $key, string ...$keys)
 * @method int storeBitwiseNot(string $destination, string $key)
 * @method int getBitPosition(string $key, bool $bit, int|null $start = null, int|null $end = null)
 * @method int decrement(string $key, int $decrement = 1)
 * @method string|null get(string $key)
 * @method bool getBit(string $key, int $offset)
 * @method string getRange(string $key, int $start = 0, int $end = -1)
 * @method string getAndSet(string $key, string $value)
 * @method int increment(string $key, int $increment = 1)
 * @method float incrementByFloat(string $key, float $increment)
 * @method array<string, string|null> getMultiple(string $key, string ...$keys)
 * @method void setMultiple(array $data)
 * @method void setMultipleWithoutOverwrite(array $data)
 * @method bool setWithoutOverwrite(string $key, string $value)
 * @method bool set(string $key, string $value, SetOptions|null $options = null)
 * @method int setBit(string $key, int $offset, bool $value)
 * @method int setRange(string $key, int $offset, string $value)
 * @method int getLength(string $key)
 * @method int publish(string $channel, string $message)
 * @method array<string> getActiveChannels(string|null $pattern = null)
 * @method array<string, int> getNumberOfSubscriptions(string ...$channels)
 * @method int getNumberOfPatternSubscriptions()
 * @method void ping()
 * @method void quit()
 * @method void rewriteAofAsync()
 * @method void saveAsync()
 * @method string|null getName()
 * @method void pauseMillis(int $timeInMillis)
 * @method void setName(string $name)
 * @method array<string, mixed> getConfig(string $parameter)
 * @method void resetStatistics()
 * @method void rewriteConfig()
 * @method void setConfig(string $parameter, string $value)
 * @method int getDatabaseSize()
 * @method void flushAll()
 * @method void flushDatabase()
 * @method int getLastSave()
 * @method array<mixed> getRole()
 * @method void save()
 * @method string shutdownWithSave()
 * @method string shutdownWithoutSave()
 * @method string shutdown()
 * @method void enableReplication(string $host, int $port)
 * @method void disableReplication()
 * @method array<mixed> getSlowlog(int|null $count = null)
 * @method int getSlowlogLength()
 * @method void resetSlowlog()
 * @method array<int, string> getTime()
 * @method bool hasScript(string $sha1)
 * @method void flushScripts()
 * @method void killScript()
 * @method string loadScript(string $script)
 * @method string echo(string $text)
 * @method mixed eval(string $script, array $keys = [], array $args = [])
 * @method void select(int $database)
 */
class ClientWrapper implements ClientContract
{
    private RedisClient $client;

    public function __construct(RedisClient $client)
    {
        $this->client = $client;
    }

    public function execute(string $command, string|int|float ...$args): mixed
    {
        return $this->client->execute($command, ...$args);
    }

    public function getClient(): RedisClient
    {
        return $this->client;
    }

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->client->{$name}(...$arguments);
    }
}
