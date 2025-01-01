<?php

declare(strict_types=1);

namespace Phenix\Database\Connections;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Amp\Redis\RedisClient;
use Closure;
use Phenix\Database\Constants\Drivers;

use function Amp\Redis\createRedisClient;

class ConnectionFactory
{
    public static function make(Drivers $driver, array $settings): Closure
    {
        return match ($driver) {
            Drivers::MYSQL => self::createMySqlConnection($settings),
            Drivers::POSTGRESQL => self::createPostgreSqlConnection($settings),
            Drivers::REDIS => self::createRedisConnection($settings),
        };
    }

    private static function createMySqlConnection(array $settings): Closure
    {
        return static function () use ($settings): MysqlConnectionPool {
            $config = new MysqlConfig(
                host: $settings['host'],
                port: (int) $settings['port'] ?: MysqlConfig::DEFAULT_PORT,
                user: $settings['username'],
                password: $settings['password'],
                database: $settings['database'],
                charset: $settings['charset'] ?: MysqlConfig::DEFAULT_CHARSET,
                collate: $settings['collation'] ?: MysqlConfig::DEFAULT_COLLATE
            );

            return new MysqlConnectionPool($config);
        };
    }

    private static function createPostgreSqlConnection(array $settings): Closure
    {
        return static function () use ($settings): PostgresConnectionPool {
            $config = new PostgresConfig(
                host: $settings['host'],
                port: (int) $settings['port'] ?: PostgresConfig::DEFAULT_PORT,
                user: $settings['username'],
                password: $settings['password'],
                database: $settings['database'],
            );

            return new PostgresConnectionPool($config);
        };
    }

    private static function createRedisConnection(array $settings): Closure
    {
        return static function () use ($settings): RedisClient {
            $auth = $settings['username'] && $settings['password']
                ? sprintf('%s:%s@', $settings['username'], $settings['password'])
                : '';

            $uri = sprintf(
                '%s://%s%s:%s/%d',
                $settings['scheme'] ?: 'redis',
                $auth,
                $settings['host'] ?: '127.0.0.1',
                $settings['port'] ?: '6379',
                (int) $settings['database'] ?: 0
            );

            return createRedisClient($uri);
        };
    }
}
