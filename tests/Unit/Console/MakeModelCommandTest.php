<?php

declare(strict_types=1);

use Phenix\Contracts\Filesystem\File;
use Phenix\Testing\Mock;
use Symfony\Component\Console\Tester\CommandTester;

it('creates model successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: function (string $path): string {
            return file_get_contents($path);
        },
        put: fn (string $path) => true,
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('make:model', [
        'name' => 'User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Model successfully generated!');
});

it('does not create the model because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:model', [
        'name' => 'User',
    ]);

    /** @var CommandTester $command */
    $command = $this->phenix('make:model', [
        'name' => 'User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Model already exists!');
});

it('creates model successfully with force option', function () {
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
    $command = $this->phenix('make:model', [
        'name' => 'User',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Model successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates model successfully in nested namespace', function () {
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
    $command = $this->phenix('make:model', [
        'name' => 'Admin/User',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Model successfully generated!');
});

it('creates model with custom collection', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path): bool => false,
        get: function (string $path): string {
            return file_get_contents($path);
        },
        put: function (string $path, string $content): bool {
            if (str_ends_with($path, 'UserCollection.php')) {
                expect($content)->toContain('namespace App\Collections;');
                expect($content)->toContain('class UserCollection extends Collection');
            }

            if (str_ends_with($path, 'User.php')) {
                expect($content)->toContain('use App\Collections\UserCollection;');
                expect($content)->toContain('class User extends DatabaseModel');
                expect($content)->toContain('public function newCollection(): UserCollection');
            }

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('make:model', [
        'name' => 'User',
        '--collection' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Model successfully generated!');
    expect($command->getDisplay())->toContain('Collection successfully generated!');
});
