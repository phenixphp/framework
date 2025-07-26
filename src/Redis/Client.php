<?php

declare(strict_types=1);

namespace Phenix\Redis;

use Traversable;
use Amp\Redis\RedisClient;
use Amp\Redis\Command\RedisMap;
use Amp\Redis\Command\RedisSet;
use Amp\Redis\Command\RedisList;
use Phenix\Redis\Contracts\Client as ClientContract;
use Amp\Redis\Command\RedisSortedSet;
use Amp\Redis\Command\RedisHyperLogLog;

class Client implements ClientContract
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

    public function getHyperLogLog(string $key): RedisHyperLogLog
    {
        return $this->client->getHyperLogLog($key);
    }

    public function getList(string $key): RedisList
    {
        return $this->client->getList($key);
    }

    public function getMap(string $key): RedisMap
    {
        return $this->client->getMap($key);
    }

    public function getSet(string $key): RedisSet
    {
        return $this->client->getSet($key);
    }

    public function getSortedSet(string $key): RedisSortedSet
    {
        return $this->client->getSortedSet($key);
    }

    public function delete(string $key, string ...$keys): int
    {
        return $this->client->delete($key, ...$keys);
    }

    public function dump(string $key): string
    {
        return $this->client->dump($key);
    }

    public function has(string $key): bool
    {
        return $this->client->has($key);
    }

    public function expireIn(string $key, int $seconds): bool
    {
        return $this->client->expireIn($key, $seconds);
    }

    public function expireInMillis(string $key, int $millis): bool
    {
        return $this->client->expireInMillis($key, $millis);
    }

    public function expireAt(string $key, int $timestamp): bool
    {
        return $this->client->expireAt($key, $timestamp);
    }

    public function expireAtMillis(string $key, int $timestamp): bool
    {
        return $this->client->expireAtMillis($key, $timestamp);
    }

    public function getKeys(string $pattern = '*'): array
    {
        return $this->client->getKeys($pattern);
    }

    public function move(string $key, int $db): bool
    {
        return $this->client->move($key, $db);
    }

    public function getObjectRefcount(string $key): int
    {
        return $this->client->getObjectRefcount($key);
    }

    public function getObjectEncoding(string $key): string
    {
        return $this->client->getObjectEncoding($key);
    }

    public function getObjectIdletime(string $key): int
    {
        return $this->client->getObjectIdletime($key);
    }

    public function persist(string $key): bool
    {
        return $this->client->persist($key);
    }

    public function getRandomKey(): ?string
    {
        return $this->client->getRandomKey();
    }

    public function rename(string $key, string $newKey): void
    {
        $this->client->rename($key, $newKey);
    }

    public function renameWithoutOverwrite(string $key, string $newKey): void
    {
        $this->client->renameWithoutOverwrite($key, $newKey);
    }

    public function restore(string $key, string $serializedValue, int $ttl = 0): void
    {
        $this->client->restore($key, $serializedValue, $ttl);
    }

    public function scan(?string $pattern = null, ?int $count = null): Traversable
    {
        return $this->client->scan($pattern, $count);
    }

    public function getTtl(string $key): int
    {
        return $this->client->getTtl($key);
    }

    public function getTtlInMillis(string $key): int
    {
        return $this->client->getTtlInMillis($key);
    }

    public function getType(string $key): string
    {
        return $this->client->getType($key);
    }

    public function append(string $key, string $value): int
    {
        return $this->client->append($key, $value);
    }

    public function countBits(string $key, ?int $start = null, ?int $end = null): int
    {
        return $this->client->countBits($key, $start, $end);
    }

    public function storeBitwiseAnd(string $destination, string $key, string ...$keys): int
    {
        return $this->client->storeBitwiseAnd($destination, $key, ...$keys);
    }

    public function storeBitwiseOr(string $destination, string $key, string ...$keys): int
    {
        return $this->client->storeBitwiseOr($destination, $key, ...$keys);
    }

    public function storeBitwiseXor(string $destination, string $key, string ...$keys): int
    {
        return $this->client->storeBitwiseXor($destination, $key, ...$keys);
    }

    public function storeBitwiseNot(string $destination, string $key): int
    {
        return $this->client->storeBitwiseNot($destination, $key);
    }

    public function getBitPosition(string $key, bool $bit, ?int $start = null, ?int $end = null): int
    {
        return $this->client->getBitPosition($key, $bit, $start, $end);
    }

    public function decrement(string $key, int $decrement = 1): int
    {
        return $this->client->decrement($key, $decrement);
    }

    public function get(string $key): ?string
    {
        return $this->client->get($key);
    }

    public function getBit(string $key, int $offset): bool
    {
        return $this->client->getBit($key, $offset);
    }

    public function getRange(string $key, int $start = 0, int $end = -1): string
    {
        return $this->client->getRange($key, $start, $end);
    }

    public function getAndSet(string $key, string $value): string
    {
        return $this->client->getAndSet($key, $value);
    }

    public function increment(string $key, int $increment = 1): int
    {
        return $this->client->increment($key, $increment);
    }

    public function incrementByFloat(string $key, float $increment): float
    {
        return $this->client->incrementByFloat($key, $increment);
    }

    public function getMultiple(string $key, string ...$keys): array
    {
        return $this->client->getMultiple($key, ...$keys);
    }

    public function setMultiple(array $data): void
    {
        $this->client->setMultiple($data);
    }

    public function setMultipleWithoutOverwrite(array $data): void
    {
        $this->client->setMultipleWithoutOverwrite($data);
    }

    public function setWithoutOverwrite(string $key, string $value): bool
    {
        return $this->client->setWithoutOverwrite($key, $value);
    }

    public function set(string $key, string $value, $options = null): bool
    {
        return $this->client->set($key, $value, $options);
    }

    public function setBit(string $key, int $offset, bool $value): int
    {
        return $this->client->setBit($key, $offset, $value);
    }

    public function setRange(string $key, int $offset, string $value): int
    {
        return $this->client->setRange($key, $offset, $value);
    }

    public function getLength(string $key): int
    {
        return $this->client->getLength($key);
    }

    public function publish(string $channel, string $message): int
    {
        return $this->client->publish($channel, $message);
    }

    public function getActiveChannels(?string $pattern = null): array
    {
        return $this->client->getActiveChannels($pattern);
    }

    public function getNumberOfSubscriptions(string ...$channels): array
    {
        return $this->client->getNumberOfSubscriptions(...$channels);
    }

    public function getNumberOfPatternSubscriptions(): int
    {
        return $this->client->getNumberOfPatternSubscriptions();
    }

    public function ping(): void
    {
        $this->client->ping();
    }

    public function quit(): void
    {
        $this->client->quit();
    }

    public function rewriteAofAsync(): void
    {
        $this->client->rewriteAofAsync();
    }

    public function saveAsync(): void
    {
        $this->client->saveAsync();
    }

    public function getName(): ?string
    {
        return $this->client->getName();
    }

    public function pauseMillis(int $timeInMillis): void
    {
        $this->client->pauseMillis($timeInMillis);
    }

    public function setName(string $name): void
    {
        $this->client->setName($name);
    }

    public function getConfig(string $parameter): array
    {
        return $this->client->getConfig($parameter);
    }

    public function resetStatistics(): void
    {
        $this->client->resetStatistics();
    }

    public function rewriteConfig(): void
    {
        $this->client->rewriteConfig();
    }

    public function setConfig(string $parameter, string $value): void
    {
        $this->client->setConfig($parameter, $value);
    }

    public function getDatabaseSize(): int
    {
        return $this->client->getDatabaseSize();
    }

    public function flushAll(): void
    {
        $this->client->flushAll();
    }

    public function flushDatabase(): void
    {
        $this->client->flushDatabase();
    }

    public function getLastSave(): int
    {
        return $this->client->getLastSave();
    }

    public function getRole(): array
    {
        return $this->client->getRole();
    }

    public function save(): void
    {
        $this->client->save();
    }

    public function shutdownWithSave(): string
    {
        return $this->client->shutdownWithSave();
    }

    public function shutdownWithoutSave(): string
    {
        return $this->client->shutdownWithoutSave();
    }

    public function shutdown(): string
    {
        return $this->client->shutdown();
    }

    public function enableReplication(string $host, int $port): void
    {
        $this->client->enableReplication($host, $port);
    }

    public function disableReplication(): void
    {
        $this->client->disableReplication();
    }

    public function getSlowlog(?int $count = null): array
    {
        return $this->client->getSlowlog($count);
    }

    public function getSlowlogLength(): int
    {
        return $this->client->getSlowlogLength();
    }

    public function resetSlowlog(): void
    {
        $this->client->resetSlowlog();
    }

    public function getTime(): array
    {
        return $this->client->getTime();
    }

    public function hasScript(string $sha1): bool
    {
        return $this->client->hasScript($sha1);
    }

    public function flushScripts(): void
    {
        $this->client->flushScripts();
    }

    public function killScript(): void
    {
        $this->client->killScript();
    }

    public function loadScript(string $script): string
    {
        return $this->client->loadScript($script);
    }

    public function echo(string $text): string
    {
        return $this->client->echo($text);
    }

    public function eval(string $script, array $keys = [], array $args = []): mixed
    {
        return $this->client->eval($script, $keys, $args);
    }

    public function select(int $database): void
    {
        $this->client->select($database);
    }
}
