<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates type successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Validation/Types/AwesomeType.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:type', [
        'name' => 'AwesomeType',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Type [app/Validation/Types/AwesomeType.php] successfully generated!');
});

it('does not create the type because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:type', [
        'name' => 'TestType',
    ]);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:type', [
        'name' => 'TestType',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Type already exists!');
});

it('creates type successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'TestType.php';

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
    $command = $this->phenix('make:type', [
        'name' => 'TestType',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Type [app/Validation/Types/TestType.php] successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates type successfully in nested namespace', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Validation/Types/Admin/TestType.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:type', [
        'name' => 'Admin/TestType',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Type [app/Validation/Types/Admin/TestType.php] successfully generated!');
});
