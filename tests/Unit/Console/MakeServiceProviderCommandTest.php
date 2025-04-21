<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates service provider successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: fn (string $path) => '',
        put: function (string $path) {
            expect($path)->toBe(base_path('app/Providers/SlackServiceProvider.php'));

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:provider', [
        'name' => 'SlackServiceProvider',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Service provider successfully generated!');
});
