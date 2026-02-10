<?php

declare(strict_types=1);

use Phenix\Console\Phenix;
use Phenix\Database\Console\DatabaseCommand;
use Phenix\Database\Console\MigrateFresh;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;

const MANAGER_CLASS = '\Phinx\Migration\Manager';

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
                'adapter' => 'mysql',
                'host' => 'host',
                'name' => 'development',
                'user' => '',
                'pass' => '',
                'port' => 3006,
            ],
        ],
    ]);

    $this->input = new ArrayInput([]);
    $this->output = new StreamOutput(fopen('php://memory', 'a', false));
});

it('executes fresh migration successfully', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(MANAGER_CLASS)
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

    $this->assertStringContainsString('using database development', $output);
    $this->assertStringContainsString('Rolling back all migrations...', $output);
    $this->assertStringContainsString('Running migrations...', $output);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('executes fresh migration with seed option', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(MANAGER_CLASS)
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
    $exitCode = $commandTester->execute(['command' => $command->getName(), '--seed' => true], ['decorated' => false]);

    $output = $commandTester->getDisplay();

    $this->assertStringContainsString('using database development', $output);
    $this->assertStringContainsString('Rolling back all migrations...', $output);
    $this->assertStringContainsString('Running migrations...', $output);
    $this->assertStringContainsString('Running seeders...', $output);
    $this->assertStringContainsString('Seeders completed.', $output);
    $this->assertSame(DatabaseCommand::CODE_SUCCESS, $exitCode);
});

it('shows correct environment information', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(MANAGER_CLASS)
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

it('handles migration errors gracefully', function () {
    $application = new Phenix();
    $application->add(new MigrateFresh());

    /** @var MigrateFresh $command */
    $command = $application->find('migrate:fresh');

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject $managerStub */
    $managerStub = $this->getMockBuilder(MANAGER_CLASS)
        ->setConstructorArgs([$this->config, $this->input, $this->output])
        ->getMock();

    $managerStub->expects($this->once())
        ->method('rollback')
        ->willThrowException(new Exception('Rollback failed'));

    $command->setConfig($this->config);
    $command->setManager($managerStub);

    $commandTester = new CommandTester($command);
    $exitCode = $commandTester->execute(['command' => $command->getName()], ['decorated' => false]);

    $this->assertSame(DatabaseCommand::CODE_ERROR, $exitCode);
});
