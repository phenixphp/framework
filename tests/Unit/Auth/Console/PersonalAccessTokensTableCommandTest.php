<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates personal access tokens table migration successfully', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path): bool => false,
        get: fn (string $path): string => file_get_contents($path),
        put: function (string $path): bool {
            $prefix = base_path('database' . DIRECTORY_SEPARATOR . 'migrations');
            if (! str_starts_with($path, $prefix)) {
                throw new RuntimeException('Migration path prefix mismatch');
            }
            if (! str_ends_with($path, 'create_personal_access_tokens_table.php')) {
                throw new RuntimeException('Migration filename suffix mismatch');
            }

            return true;
        },
        createDirectory: function (string $path): void {
            // Directory creation is mocked
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('tokens:table');

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Personal access tokens table [database/migrations/20251128110000_create_personal_access_tokens_table.php] successfully generated!');
});
