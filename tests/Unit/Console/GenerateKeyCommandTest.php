<?php

declare(strict_types=1);

use Phenix\Crypto\Cipher;
use Phenix\Facades\Config;
use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

it('sets the application key', function () {
    $mock = Mock::of(File::class)->expect(
        get: function (string $path): string {
            return 'APP_KEY=' . PHP_EOL;
        },
        put: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('key:generate');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Application key set successfully!');
});

it('does not set the application key if it is already set', function () {
    Config::set('app.key', Cipher::generateEncodedKey());

    /** @var CommandTester $command */
    $command = $this->phenix('key:generate');

    expect($command->getStatusCode())->toBe(Command::FAILURE);
    expect($command->getDisplay())->toContain('Application key is already set. Use --force to override it.');
});

it('fails on set application key', function () {
    $mock = Mock::of(File::class)->expect(
        get: fn (string $path): string => '',
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('key:generate');

    expect($command->getStatusCode())->toBe(Command::FAILURE);
    expect($command->getDisplay())->toContain('Failed to set the application key');
});

it('sets application key with force option', function () {
    Config::set('app.key', 'base64:' . base64_encode(random_bytes(32)));

    $mock = Mock::of(File::class)->expect(
        get: function (string $path): string {
            return 'APP_KEY=' . PHP_EOL;
        },
        put: fn (string $path) => true,
    );

    $this->app->swap(File::class, $mock);

    /** @var CommandTester $command */
    $command = $this->phenix('key:generate', [
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Application key set successfully!');
});
