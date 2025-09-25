<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates listener successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Listeners/AwesomeListener.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:listener', [
        'name' => 'AwesomeListener',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Listener [app/Listeners/AwesomeListener.php] successfully generated!');
});

it('does not create the listener because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:listener', [
        'name' => 'TestListener',
    ]);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:listener', [
        'name' => 'TestListener',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Listener already exists!');
});

it('creates listener successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'TestListener.php';

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

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:listener', [
        'name' => 'TestListener',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Listener [app/Listeners/TestListener.php] successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates listener successfully in nested namespace', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Listeners/Admin/TestListener.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:listener', [
        'name' => 'Admin/TestListener',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Listener [app/Listeners/Admin/TestListener.php] successfully generated!');
});
