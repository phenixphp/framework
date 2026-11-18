<?php

declare(strict_types=1);

use Phenix\Packages\PackageManifest;

beforeEach(function (): void {
    $this->packageApp = base_path('storage/framework/testing/package-manifest-' . uniqid());

    mkdir("{$this->packageApp}/vendor/composer", 0755, true);
    mkdir("{$this->packageApp}/storage/framework/cache", 0755, true);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->packageApp, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->packageApp);
});

it('discovers providers from composer metadata', function (): void {
    file_put_contents("{$this->packageApp}/composer.json", json_encode([
        'extra' => ['phenix' => ['dont-discover' => []]],
    ], JSON_THROW_ON_ERROR));

    file_put_contents("{$this->packageApp}/vendor/composer/installed.json", json_encode([
        'packages' => [
            [
                'name' => 'acme/blog',
                'extra' => ['phenix' => ['providers' => ['Acme\\Blog\\BlogServiceProvider']]],
            ],
            ['name' => 'acme/plain'],
        ],
    ], JSON_THROW_ON_ERROR));

    $manifest = new PackageManifest($this->packageApp);
    $manifest->build();

    expect($manifest->providers())->toBe(['Acme\\Blog\\BlogServiceProvider'])
        ->and($manifest->packages())->toBe(['acme/blog'])
        ->and($manifest->path())->toBeFile();
});

it('honors application package discovery exclusions', function (): void {
    file_put_contents("{$this->packageApp}/composer.json", json_encode([
        'extra' => ['phenix' => ['dont-discover' => ['acme/blog']]],
    ], JSON_THROW_ON_ERROR));

    file_put_contents("{$this->packageApp}/vendor/composer/installed.json", json_encode([
        [
            'name' => 'acme/blog',
            'extra' => ['phenix' => ['providers' => ['Acme\\Blog\\BlogServiceProvider']]],
        ],
    ], JSON_THROW_ON_ERROR));

    $manifest = new PackageManifest($this->packageApp);
    $manifest->build();

    expect($manifest->providers())->toBe([]);
});
