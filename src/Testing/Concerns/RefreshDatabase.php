<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Amp\Sql\Common\SqlCommonConnectionPool;
use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
use Phinx\Config\Config as MigrationConfig;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Throwable;

trait RefreshDatabase
{
    protected static bool $migrated = false;

    protected function refreshDatabase(): void
    {
        if (! static::$migrated) {
            $this->runMigrations();

            static::$migrated = true;
        }

        $this->truncateDatabase();
    }

    protected function runMigrations(): void
    {
        $defaultConnection = Config::get('database.default');
        $settings = Config::get("database.connections.{$defaultConnection}");

        $driver = Driver::tryFrom($settings['driver']) ?? Driver::MYSQL;

        $config = new MigrationConfig([
            'paths' => [
                'migrations' => Config::get('database.paths.migrations'),
                'seeds' => Config::get('database.paths.seeds'),
            ],
            'environments' => [
                'default_migration_table' => 'migrations',
                'default_environment' => 'default',
                'default' => [
                    'adapter' => $driver->value,
                    'host' => $settings['host'] ?? null,
                    'name' => $settings['database'] ?? null,
                    'user' => $settings['username'] ?? null,
                    'pass' => $settings['password'] ?? null,
                    'port' => $settings['port'] ?? null,
                ],
            ],
        ]);

        $manager = new Manager($config, new ArrayInput([]), new NullOutput());

        try {
            $manager->migrate('default');
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function truncateDatabase(): void
    {
        /** @var SqlCommonConnectionPool $connection */
        $connection = App::make(Connection::default());

        $driver = $this->resolveDriver();

        try {
            $tables = $this->getDatabaseTables($connection, $driver);
        } catch (Throwable) {
            return;
        }

        $tables = $this->filterTruncatableTables($tables);

        if (empty($tables)) {
            return;
        }

        $this->truncateTables($connection, $driver, $tables);
    }

    protected function resolveDriver(): Driver
    {
        $defaultConnection = Config::get('database.default');
        $settings = Config::get("database.connections.{$defaultConnection}");

        return Driver::tryFrom($settings['driver']) ?? Driver::MYSQL;
    }

    /**
     * @return array<int, string>
     */
    protected function getDatabaseTables(SqlCommonConnectionPool $connection, Driver $driver): array
    {
        $tables = [];

        if ($driver === Driver::MYSQL) {
            $result = $connection->prepare('SHOW TABLES')->execute();

            foreach ($result as $row) {
                $table = array_values($row)[0] ?? null;

                if ($table) {
                    $tables[] = $table;
                }
            }
        } elseif ($driver === Driver::POSTGRESQL) {
            $result = $connection->prepare("SELECT tablename FROM pg_tables WHERE schemaname = 'public'")->execute();

            foreach ($result as $row) {
                $table = $row['tablename'] ?? null;

                if ($table) {
                    $tables[] = $table;
                }
            }
        } else {
            // Unsupported driver (sqlite, etc.) – return empty so caller exits gracefully.
            return [];
        }

        return $tables;
    }

    /**
     * @param array<int, string> $tables
     * @return array<int, string>
     */
    protected function filterTruncatableTables(array $tables): array
    {
        return array_values(array_filter(
            $tables,
            static fn (string $t): bool => $t !== 'migrations'
        ));
    }

    /**
     * @param array<int, string> $tables
     */
    protected function truncateTables(SqlCommonConnectionPool $connection, Driver $driver, array $tables): void
    {
        try {
            if ($driver === Driver::MYSQL) {
                $connection->prepare('SET FOREIGN_KEY_CHECKS=0')->execute();

                foreach ($tables as $table) {
                    $connection->prepare('TRUNCATE TABLE `'.$table.'`')->execute();
                }

                $connection->prepare('SET FOREIGN_KEY_CHECKS=1')->execute();
            } elseif ($driver === Driver::POSTGRESQL) {
                $quoted = array_map(static fn (string $t): string => '"' . str_replace('"', '""', $t) . '"', $tables);

                $connection->prepare('TRUNCATE TABLE '.implode(', ', $quoted).' RESTART IDENTITY CASCADE')->execute();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
