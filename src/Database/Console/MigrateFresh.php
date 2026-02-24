<?php

declare(strict_types=1);

namespace Phenix\Database\Console;

use Amp\Sql\SqlConnection;
use Exception;
use Phenix\App;
use Phenix\Database\Constants\Connection;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'migrate:fresh')]
class MigrateFresh extends DatabaseCommand
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'migrate:fresh';

    /**
     * Time format constant
     */
    private const TIME_FORMAT = '%.4fs';

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('--environment', '-e', InputOption::VALUE_REQUIRED, 'The target environment', 'default');

        $this->setDescription('Drop all tables and re-run all migrations')
            ->addOption('--seed', '-s', InputOption::VALUE_NONE, 'Run seeders after migrations')
            ->addOption(
                '--dry-run',
                '-x',
                InputOption::VALUE_NONE,
                'Dump query to standard output instead of executing it'
            )
            ->setHelp(
                <<<EOT
The <info>migrate:fresh</info> command drops all tables from the database and re-runs all migrations

<info>php phenix migrate:fresh</info>
<info>php phenix migrate:fresh --seed</info>
<info>php phenix migrate:fresh -v</info>

This command is useful for development when you want to reset your database to a clean state.

EOT
            );
    }

    /**
     * Drop all tables and run migrations.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     * @return int integer 0 on success, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap($input, $output);

        /** @var string|null $environment */
        $environment = $input->getOption('environment');
        $seed = (bool)$input->getOption('seed');

        $output->writeln('<info>using environment</info> ' . $environment, $this->verbosityLevel);

        $envOptions = $this->getConfig()->getEnvironment($environment);

        $output->writeln('<info>using database</info> ' . $envOptions['name'], $this->verbosityLevel);

        try {
            // Rollback all migrations first
            $output->writeln('<comment>Rolling back all migrations...</comment>', $this->verbosityLevel);
            $start = microtime(true);
            $this->getManager()->rollback($environment, 0, true);
            $rollbackEnd = microtime(true);
            $output->writeln(
                '<info>Rollback completed. Took ' . sprintf(self::TIME_FORMAT, $rollbackEnd - $start) . '</info>',
                $this->verbosityLevel
            );

            // Drop all tables to ensure clean state
            $output->writeln('<comment>Dropping all tables...</comment>', $this->verbosityLevel);
            $this->dropAllTables($output);

            // Run migrations
            $output->writeln('<comment>Running migrations...</comment>', $this->verbosityLevel);
            $migrateStart = microtime(true);
            $this->getManager()->migrate($environment, null, false);
            $migrateEnd = microtime(true);

            $output->writeln('', $this->verbosityLevel);
            $output->writeln(
                '<comment>Migrations completed. Took ' . sprintf(self::TIME_FORMAT, $migrateEnd - $migrateStart) . '</comment>',
                $this->verbosityLevel
            );

            // Run seeders if requested
            if ($seed) {
                $output->writeln('<comment>Running seeders...</comment>', $this->verbosityLevel);
                $seedStart = microtime(true);
                $this->getManager()->seed($environment);
                $seedEnd = microtime(true);

                $output->writeln('', $this->verbosityLevel);
                $output->writeln(
                    '<comment>Seeders completed. Took ' . sprintf(self::TIME_FORMAT, $seedEnd - $seedStart) . '</comment>',
                    $this->verbosityLevel
                );
            }

            $totalEnd = microtime(true);
            $output->writeln('', $this->verbosityLevel);
            $output->writeln(
                '<comment>All Done. Total time: ' . sprintf(self::TIME_FORMAT, $totalEnd - $start) . '</comment>',
                $this->verbosityLevel
            );
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->__toString() . '</error>');

            return self::CODE_ERROR;
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->__toString() . '</error>');

            return self::CODE_ERROR;
        }

        return self::CODE_SUCCESS;
    }

    /**
     * Drop all tables from the database.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     * @return void
     */
    protected function dropAllTables(OutputInterface $output): void
    {
        try {
            /** @var SqlConnection|object $connection */
            $connection = App::make(Connection::default());

            $driver = $this->resolveDriver();

            if ($driver === Driver::SQLITE) {
                $this->dropAllSqliteTables($connection, $output);

                return;
            }

            $tables = $this->getDatabaseTables($connection, $driver);

            if (empty($tables)) {
                $output->writeln('<info>No tables to drop.</info>', $this->verbosityLevel);

                return;
            }

            $this->dropTables($connection, $driver, $tables, $output);
        } catch (Throwable $e) {
            // If we can't connect to database, migrations manager will handle table creation
            $output->writeln(
                '<comment>Unable to drop tables directly, relying on rollback: ' . $e->getMessage() . '</comment>',
                $this->verbosityLevel
            );
        }
    }

    /**
     * Resolve the database driver.
     *
     * @return Driver
     */
    protected function resolveDriver(): Driver
    {
        $defaultConnection = Config::get('database.default');
        $settings = Config::get("database.connections.{$defaultConnection}");

        return Driver::tryFrom($settings['driver']) ?? Driver::MYSQL;
    }

    /**
     * Get all tables from the database.
     *
     * @param SqlConnection $connection Database connection
     * @param Driver $driver Database driver
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
        }

        return $tables;
    }

    /**
     * Drop tables from MySQL or PostgreSQL database.
     *
     * @param SqlConnection $connection Database connection
     * @param Driver $driver Database driver
     * @param array<int, string> $tables Tables to drop
     * @param OutputInterface $output Output
     * @return void
     */
    protected function dropTables(
        SqlConnection $connection,
        Driver $driver,
        array $tables,
        OutputInterface $output
    ): void {
        try {
            if ($driver === Driver::MYSQL) {
                $connection->prepare('SET FOREIGN_KEY_CHECKS=0')->execute();

                foreach ($tables as $table) {
                    $output->writeln(
                        "<info>Dropping table:</info> {$table}",
                        $this->verbosityLevel
                    );
                    $connection->prepare('DROP TABLE IF EXISTS `' . $table . '`')->execute();
                }

                $connection->prepare('SET FOREIGN_KEY_CHECKS=1')->execute();
            } elseif ($driver === Driver::POSTGRESQL) {
                foreach ($tables as $table) {
                    $output->writeln(
                        "<info>Dropping table:</info> {$table}",
                        $this->verbosityLevel
                    );
                    $quoted = '"' . str_replace('"', '""', $table) . '"';
                    $connection->prepare('DROP TABLE IF EXISTS ' . $quoted . ' CASCADE')->execute();
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to drop tables: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Drop all tables from SQLite database.
     *
     * @param SqlConnection $connection Database connection
     * @param OutputInterface $output Output
     * @return void
     */
    protected function dropAllSqliteTables(SqlConnection $connection, OutputInterface $output): void
    {
        try {
            $stmt = $connection->prepare(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            );
            $result = $stmt->execute();

            $tables = [];

            foreach ($result as $row) {
                $table = $row['name'] ?? null;

                if ($table) {
                    $tables[] = $table;
                }
            }

            if (empty($tables)) {
                $output->writeln('<info>No tables to drop.</info>', $this->verbosityLevel);

                return;
            }

            $connection->prepare('PRAGMA foreign_keys = OFF')->execute();

            foreach ($tables as $table) {
                $output->writeln(
                    "<info>Dropping table:</info> {$table}",
                    $this->verbosityLevel
                );
                $quoted = '"' . str_replace('"', '""', $table) . '"';
                $connection->prepare('DROP TABLE IF EXISTS ' . $quoted)->execute();
            }

            $connection->prepare('PRAGMA foreign_keys = ON')->execute();
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to drop SQLite tables: ' . $e->getMessage(), 0, $e);
        }
    }
}
