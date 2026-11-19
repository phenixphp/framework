<?php

declare(strict_types=1);

namespace Phenix\Database\Console;

use Phenix\App;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
use Phenix\Packages\PackageRegistry;
use Phinx\Config\Config as MigrationConfig;
use Phinx\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputOption;

abstract class DatabaseCommand extends AbstractCommand
{
    public function __construct()
    {
        $defaultConnection = Config::get('database.default');

        $settings = Config::get("database.connections.{$defaultConnection}");

        $driver = Driver::tryFrom($settings['driver']) ?? Driver::MYSQL;

        $environment = [
            'adapter' => $driver->value,
            'host' => $settings['host'] ?? '',
            'name' => $settings['database'],
            'user' => $settings['username'] ?? '',
            'pass' => $settings['password'] ?? '',
            'port' => $settings['port'] ?? '',
        ];

        if ($driver === Driver::SQLITE) {
            $environment['suffix'] = '';
        }

        /** @var PackageRegistry $packages */
        $packages = App::make(PackageRegistry::class);
        $migrationPaths = array_values(array_unique([
            ...(array) Config::get('database.paths.migrations'),
            ...$packages->migrationPaths(),
        ]));

        $this->config = new MigrationConfig([
            'paths' => [
                'migrations' => $migrationPaths,
                'seeds' => Config::get('database.paths.seeds'),
            ],
            'environments' => [
                'default_migration_table' => 'migrations',
                'default_environment' => 'default',
                'default' => $environment,
            ],
        ]);

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('--no-info', null, InputOption::VALUE_NONE, 'Hides all debug information');
    }
}
