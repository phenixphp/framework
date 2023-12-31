<?php

declare(strict_types=1);

use Phenix\Contracts\Filesystem\File;
use Phenix\Testing\Mock;

it('creates test successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toContain('Feature' . DIRECTORY_SEPARATOR .'ExampleTest');

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:test', [
        'name' => 'ExampleTest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Test successfully generated!');
});

it('does not create the test because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:test', [
        'name' => 'ExampleTest',
    ]);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:test', [
        'name' => 'ExampleTest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Test already exists!');
});

it('creates test successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'ExampleTest.php';

    file_put_contents($tempPath, 'old content');

    expect('old content')->toBe(file_get_contents($tempPath));

    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => 'new content',
        put: fn (string $path, string $content) => file_put_contents($tempPath, $content),
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:test', [
        'name' => 'ExampleTest',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Test successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates test successfully in nested namespace', function () {
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
    $command = $this->phenix('make:test', [
        'name' => 'Admin/ExampleTest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Test successfully generated!');
});

it('creates test successfully with unit option', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toContain('Unit' . DIRECTORY_SEPARATOR .'ExampleTest');

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:test', [
        'name' => 'ExampleTest',
        '--unit' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Test successfully generated!');
});
