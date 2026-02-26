<?php

declare(strict_types=1);

use Phenix\Console\Phenix;
use Phenix\Database\Console\DatabaseCommand;
use Phenix\Database\Console\MigrateFresh;
use Phenix\Database\Constants\Driver;
use Phenix\Facades\Config as Configuration;
use Phenix\Facades\DB;
use Phenix\Facades\File;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

const SQLITE_MANAGER_CLASS = '\Phinx\Migration\Manager';

beforeEach(function () {
    $this->config = new Config([
        'paths' => [
            'migrations' => __FILE__,
            'seeds' => __FILE__,
        ],
        'environments' => [
            'default_migration_table' => 'migrations',
            'default_environment' => 'default',
            'default' => [
                'adapter' => 'sqlite',
                'name' => ':memory:',
                'suffix' => '',
            ],
        ],
    ]);

    $this->input = new ArrayInput([]);
    $this->output = new StreamOutput(fopen('php://memory', 'a', false));

    Configuration::set('database.default', Driver::SQLITE->value);
});

it('executes fresh migration with sqlite adapter successfully', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->with('default', 0, true);

    $managerStub->expects($this->once())
        ->method('migrate');

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

    $output = $commandTester->getDisplay();

    $this->assertStringContainsString('using database :memory:', $output);
    $this->assertStringContainsString('Rolling back all migrations...', $output);
    $this->assertStringContainsString('Running migrations...', $output);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('executes fresh migration with sqlite adapter and seed option', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->with('default', 0, true);

    $managerStub->expects($this->once())
        ->method('migrate');

    $managerStub->expects($this->once())
        ->method('seed');

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(
        ['command' => $command->getName(), '--seed' => true],
        ['decorated' => false]
    );

    $output = $commandTester->getDisplay();

    $this->assertStringContainsString('using database :memory:', $output);
    $this->assertStringContainsString('Rolling back all migrations...', $output);
    $this->assertStringContainsString('Running migrations...', $output);
    $this->assertStringContainsString('Running seeders...', $output);
    $this->assertStringContainsString('Seeders completed.', $output);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('shows correct environment information with sqlite adapter', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback');

    $managerStub->expects($this->once())
        ->method('migrate');

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(
        ['command' => $command->getName(), '--environment' => 'default'],
        ['decorated' => false]
    );

    $output = $commandTester->getDisplay();

    $this->assertStringContainsString('using environment default', $output);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('handles migration errors gracefully with sqlite adapter', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->willThrowException(new Exception('SQLite rollback failed'));

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

    $output = $commandTester->getDisplay();

    $this->assertStringContainsString('SQLite rollback failed', $output);
    $this->assertSame(DatabaseCommand::CODE_ERROR, $exitCode);
});

it('uses sqlite adapter configuration without host or port', function () {
    $sqliteConfig = new Config([
        'paths' => [
            'migrations' => __FILE__,
            'seeds' => __FILE__,
        ],
        'environments' => [
            'default_migration_table' => 'migrations',
            'default_environment' => 'default',
            'default' => [
                'adapter' => 'sqlite',
                'name' => '/tmp/test_database.sqlite',
                'suffix' => '',
            ],
        ],
    ]);

    $envConfig = $sqliteConfig->getEnvironment('default');

    expect($envConfig['adapter'])->toBe('sqlite');
    expect($envConfig['name'])->toBe('/tmp/test_database.sqlite');
    expect($envConfig)->not->toHaveKey('host');
    expect($envConfig)->not->toHaveKey('port');
    expect($envConfig)->not->toHaveKey('user');
    expect($envConfig)->not->toHaveKey('pass');
});

it('handles dry-run option with sqlite adapter', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->with('default', 0, true);

    $managerStub->expects($this->once())
        ->method('migrate');

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(
        ['command' => $command->getName(), '--dry-run' => true],
        ['decorated' => false]
    );

    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('executes fresh migration with sqlite file-based database', function (): void {
    Configuration::set('database.connections.sqlite.database', '/tmp/phenix_test.sqlite');

    DB::connection('sqlite')->unprepared("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT,
            created_at TEXT,
            updated_at TEXT
        )
    ");

    $fileConfig = new Config([
        'paths' => [
            'migrations' => __FILE__,
            'seeds' => __FILE__,
        ],
        'environments' => [
            'default_migration_table' => 'migrations',
            'default_environment' => 'default',
            'default' => [
                'adapter' => 'sqlite',
                'name' => '/tmp/phenix_test.sqlite',
                'suffix' => '',
            ],
        ],
    ]);

    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    $input = new ArrayInput([]);
    $output = new StreamOutput(fopen('php://memory', 'a', false));

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(SQLITE_MANAGER_CLASS)
        ->setConstructorArgs([$fileConfig, $input, $output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->with('default', 0, true);

    $managerStub->expects($this->once())
        ->method('migrate');

    $command->setConfig($fileConfig);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

    $commandOutput = $commandTester->getDisplay();

    $this->assertStringContainsString('using database /tmp/phenix_test.sqlite', $commandOutput);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);

    File::deleteFile('/tmp/phenix_test.sqlite');
});
