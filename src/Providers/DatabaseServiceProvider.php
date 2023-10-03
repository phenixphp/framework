<?php

declare(strict_types=1);

namespace Phenix\Providers;

use Phenix\Database\Connections\ConnectionFactory;
use Phenix\Database\Console\MakeMigration;
use Phenix\Database\Console\MakeSeeder;
use Phenix\Database\Console\Migrate;
use Phenix\Database\Console\Rollback;
use Phenix\Database\Console\SeedRun;
use Phenix\Database\Constants\Connections;
use Phenix\Database\Constants\Drivers;
use Phenix\Database\QueryBuilder;
use Phenix\Facades\Config;
use League\Container\Argument\ResolvableArgument;

class DatabaseServiceProvider extends ServiceProvider
{
    public function provides(string $id): bool
    {
        $this->provided = [
            QueryBuilder::class,
            Connections::name('default'),
            Connections::name('mysql'),
            Connections::name('postgresql'),
        ];

        return parent::provides($id);
    }

    public function register(): void
    {
        $connections = array_filter(array_keys(Config::get('database.connections')), function (string $connection) {
            return $connection !== Config::get('database.default');
        });

        foreach ($connections as $connection) {
            $settings = Config::get('database.connections.' . $connection);

            /** @var Drivers $driver */
            $driver = $settings['driver'];

            $callback = ConnectionFactory::make($driver, $settings);

            $this->bind(Connections::name($connection), $callback);
        }

        $this->bind(QueryBuilder::class);
    }

    public function boot(): void
    {
        $defaultConnection = Config::get('database.default');

        $settings = Config::get('database.connections.' . $defaultConnection);

        /** @var Drivers $driver */
        $driver = $settings['driver'];

        $callback = ConnectionFactory::make($driver, $settings);

        $this->bind(Connections::name('default'), new ResolvableArgument(Connections::name($defaultConnection)));

        $this->bind(Connections::name($defaultConnection), $callback);

        $this->commands([
            MakeMigration::class,
            MakeSeeder::class,
            Migrate::class,
            Rollback::class,
            SeedRun::class,
        ]);
    }
}
