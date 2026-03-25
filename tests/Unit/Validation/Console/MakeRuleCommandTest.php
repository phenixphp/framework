<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates rule successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Validation/Rules/AwesomeRule.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:rule', [
        'name' => 'AwesomeRule',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Rule [app/Validation/Rules/AwesomeRule.php] successfully generated!');
});

it('does not create the rule because it already exists', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    $this->phenix('make:rule', [
        'name' => 'TestRule',
    ]);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:rule', [
        'name' => 'TestRule',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Rule already exists!');
});

it('creates rule successfully with force option', function () {
    $tempDir = sys_get_temp_dir();
    $tempPath = $tempDir . DIRECTORY_SEPARATOR . 'TestRule.php';

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
    $command = $this->phenix('make:rule', [
        'name' => 'TestRule',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Rule [app/Validation/Rules/TestRule.php] successfully generated!');
    expect('new content')->toBe(file_get_contents($tempPath));
});

it('creates rule successfully in nested namespace', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Validation/Rules/Admin/TestRule.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:rule', [
        'name' => 'Admin/TestRule',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Rule [app/Validation/Rules/Admin/TestRule.php] successfully generated!');
});
