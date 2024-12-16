<?php

declare(strict_types=1);

use Phenix\Contracts\Filesystem\File;
use Phenix\Testing\Mock;
use Symfony\Component\Console\Tester\CommandTester;

it('creates collection successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: fn (string $path) => true,
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('make:collection', [
        'name' => 'User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Collection successfully generated!');
});

it('does not create the collection because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:collection', [
        'name' => 'User',
    ]);

    /** @var CommandTester $command */
    $command = $this->phenix('make:collection', [
        'name' => 'User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Collection already exists!');
});

it('creates collection successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'User.php';

    file_put_contents($tempPath, 'old content');

    $this->assertEquals('old content', file_get_contents($tempPath));

    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => 'new content',
        put: fn (string $path, string $content) => file_put_contents($tempPath, $content),
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('make:collection', [
        'name' => 'User',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Collection successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates collection successfully in nested namespace', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: fn (string $path) => true,
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:collection', [
        'name' => 'Admin/User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Collection successfully generated!');
});
