<?php

declare(strict_types=1);

use Phenix\Testing\Mock;
use Phenix\Filesystem\Contracts\File;

it('creates queue table successfully', function () {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path): bool => false,
        get: fn (string $path): string => file_get_contents($path),
        put: function (string $path): bool {
            expect($path)->toStartWith(base_path('database' . DIRECTORY_SEPARATOR . 'migrations'));
            expect($path)->toEndWith('create_tasks_table.php');

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('queue:table');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Queue table successfully generated!');
});
