<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Connections\ConnectionFactory;
use Phenix\Database\Console\MakeMigration;
use Phenix\Database\Console\MakeSeeder;
use Phenix\Database\Console\Migrate;
use Phenix\Database\Console\MigrateFresh;
use Phenix\Database\Console\Rollback;
use Phenix\Database\Console\SeedRun;
use Phenix\Database\Constants\Connection;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
use Phenix\Providers\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            QueryBuilder::class,
            Connection::name('default'),
            Connection::name('mysql'),
            Connection::name('postgresql'),
            Connection::name('sqlite'),
            Connection::redis('default'),
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $connections = array_keys(Config::get('database.connections'));

        foreach ($connections as $connection) {
            $settings = Config::get("database.connections.{$connection}");

            $driver = Driver::tryFrom($settings['driver']) ?? Driver::MYSQL;

            $callback = ConnectionFactory::make($driver, $settings);

            $this->bind(Connection::name($connection), $callback);
        }

        $this->registerRedisConnections();

        $this->bind(QueryBuilder::class);
    }

    public function boot(): void
    {
        $defaultConnection = Config::get('database.default');

        $this->bind(
            Connection::name('default'),
            fn () => $this->getContainer()->get(Connection::name($defaultConnection))
        );

        $this->commands([
            MakeMigration::class,
            MakeSeeder::class,
            Migrate::class,
            MigrateFresh::class,
            Rollback::class,
            SeedRun::class,
        ]);
    }

    private function registerRedisConnections(): void
    {
        $connections = Config::get('database.redis.connections');

        foreach ($connections as $connection => $settings) {
            $callback = ConnectionFactory::make(Driver::REDIS, $settings);

            $this->bind(Connection::redis($connection), $callback);
        }
    }
}
