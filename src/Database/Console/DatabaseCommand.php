<?php

declare(strict_types=1);

namespace Phenix\Database\Console;

use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config;
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

        $this->config = new MigrationConfig([
            'paths' => [
                'migrations' => Config::get('database.paths.migrations'),
                'seeds' => Config::get('database.paths.seeds'),
            ],
            'environments' => [
                'default_migration_table' => 'migrations',
                'default_environment' => 'default',
                'default' => [
                    'adapter' => $driver->value,
                    'host' => $settings['host'],
                    'name' => $settings['database'],
                    'user' => $settings['username'],
                    'pass' => $settings['password'],
                    'port' => $settings['port'],
                ],
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
