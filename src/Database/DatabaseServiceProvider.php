<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Connections\ConnectionFactory;
use Phenix\Database\Console\MakeMigration;
use Phenix\Database\Console\MakeSeeder;
use Phenix\Database\Console\Migrate;
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
            Connection::redis('default'),
        ];

        return $this->isProvided($id);
    }

    public function register(): void
    {
        $connections = array_filter(array_keys(Config::get('database.connections')), function (string $connection) {
            return $connection !== Config::get('database.default');
        });

        foreach ($connections as $connection) {
            $settings = Config::get('database.connections.' . $connection);

            /** @var Driver $driver */
            $driver = $settings['driver'];

            $callback = ConnectionFactory::make($driver, $settings);

            $this->bind(Connection::name($connection), $callback);
        }

        $this->registerRedisConnections();

        $this->bind(QueryBuilder::class);
    }

    public function boot(): void
    {
        $defaultConnection = Config::get('database.default');

        $settings = Config::get('database.connections.' . $defaultConnection);

        /** @var Driver $driver */
        $driver = $settings['driver'];

        $callback = ConnectionFactory::make($driver, $settings);

        $this->bind(Connection::name('default'), $callback);

        $this->bind(Connection::name($defaultConnection), $callback());

        $this->commands([
            MakeMigration::class,
            MakeSeeder::class,
            Migrate::class,
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
