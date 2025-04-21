<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates form request successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Http/Requests/StoreUserRequest.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:request', [
        'name' => 'StoreUserRequest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Request successfully generated!');
});

it('does not create the form request because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:request', [
        'name' => 'StoreUserRequest',
    ]);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:request', [
        'name' => 'StoreUserRequest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Request already exists!');
});

it('creates form request successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'StoreUserRequest.php';

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
    $command = $this->phenix('make:request', [
        'name' => 'StoreUserRequest',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Request successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates form request successfully in nested namespace', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Http/Requests/Admin/StoreUserRequest.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:request', [
        'name' => 'Admin/StoreUserRequest',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Request successfully generated!');
});
