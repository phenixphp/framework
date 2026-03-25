<?php

declare(strict_types=1);

namespace Phenix\Database\Connections;

use Amp\Mysql\MysqlConfig;
use Amp\Mysql\MysqlConnectionPool;
use Amp\Postgres\PostgresConfig;
use Amp\Postgres\PostgresConnectionPool;
use Closure;
use Phenix\Database\Constants\Driver;
use Phenix\Redis\ClientWrapper;
use Phenix\Sqlite\SqliteConfig;
use Phenix\Sqlite\SqliteConnection;
use SensitiveParameter;

use function Amp\Redis\createRedisClient;
use function Phenix\Sqlite\connect;
use function sprintf;

class ConnectionFactory
{
    public static function make(Driver $driver, #[SensitiveParameter] array $settings): Closure
    {
        return match ($driver) {
            Driver::MYSQL => self::createMySqlConnection($settings),
            Driver::POSTGRESQL => self::createPostgreSqlConnection($settings),
            Driver::REDIS => self::createRedisConnection($settings),
            Driver::SQLITE => self::createSqliteConnection($settings),
        };
    }

    private static function createSqliteConnection(#[SensitiveParameter] array $settings): Closure
    {
        $config = SqliteConfig::fromPath($settings['database']);

        return static fn (): SqliteConnection => connect($config);
    }

    private static function createMySqlConnection(#[SensitiveParameter] array $settings): Closure
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

    private static function createPostgreSqlConnection(#[SensitiveParameter] array $settings): Closure
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

    private static function createRedisConnection(#[SensitiveParameter] array $settings): Closure
    {
        return static function () use ($settings): ClientWrapper {
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

            return new ClientWrapper(createRedisClient($uri));
        };
    }
}
