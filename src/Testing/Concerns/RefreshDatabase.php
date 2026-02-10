<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Amp\Sql\SqlConnection;
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

        $databaseName = $settings['database'] ?? 'database';
        
        if ($driver === Driver::SQLITE) {
            $databaseName = preg_replace('/\.sqlite3?$/', '', $databaseName);
        }

        $environment = [
            'adapter' => $driver->value,
            'host' => $settings['host'] ?? null,
            'name' => $databaseName,
            'user' => $settings['username'] ?? null,
            'pass' => $settings['password'] ?? null,
            'port' => $settings['port'] ?? null,
        ];

        if ($driver === Driver::SQLITE) {
            $environment['suffix'] = '.sqlite3';
        }

        $config = new MigrationConfig([
            'paths' => [
                'migrations' => Config::get('database.paths.migrations'),
                'seeds' => Config::get('database.paths.seeds'),
            ],
            'environments' => [
                'default_migration_table' => 'migrations',
                'default_environment' => 'default',
                'default' => $environment,
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
        /** @var SqlConnection|object $connection */
        $connection = App::make(Connection::default());

        $driver = $this->resolveDriver();

        if ($driver === Driver::SQLITE) {
            try {
                $this->truncateSqliteDatabase($connection);
            } catch (Throwable $e) {
                report($e);
            }

            return;
        }

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
    protected function getDatabaseTables(SqlConnection $connection, Driver $driver): array
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
    protected function truncateTables(SqlConnection $connection, Driver $driver, array $tables): void
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

    protected function truncateSqliteDatabase(object $connection): void
    {
        $stmt = $connection->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
        $result = $stmt->execute();

        $tables = [];

        foreach ($result as $row) {
            $table = $row['name'] ?? null;

            if ($table) {
                $tables[] = $table;
            }
        }

        $tables = $this->filterTruncatableTables($tables);

        if (empty($tables)) {
            return;
        }

        try {
            $connection->prepare('BEGIN IMMEDIATE')->execute();
        } catch (Throwable) {
            // If BEGIN fails, continue best-effort without explicit transaction
        }

        try {
            foreach ($tables as $table) {
                $connection->prepare('DELETE FROM ' . '"' . str_replace('"', '""', $table) . '"')->execute();
            }

            try {
                $connection->prepare('DELETE FROM sqlite_sequence')->execute();
            } catch (Throwable) {
                // Best-effort reset of AUTOINCREMENT sequences; ignore errors
            }
        } finally {
            try {
                $connection->prepare('COMMIT')->execute();
            } catch (Throwable) {
                // Best-effort commit; ignore errors
            }
        }
    }
}
