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


it('refreshes cached packages when composer metadata or exclusions change', function (): void {
    $installed = "{$this->packageApp}/vendor/composer/installed.json";
    $composer = "{$this->packageApp}/composer.json";

    file_put_contents($composer, '{}');
    file_put_contents($installed, json_encode(['packages' => [[
        'name' => 'acme/blog', 'extra' => ['phenix' => ['providers' => ['BlogProvider']]],
    ]]], JSON_THROW_ON_ERROR));

    expect((new PackageManifest($this->packageApp))->providers())->toBe(['BlogProvider']);

    file_put_contents($composer, json_encode(['extra' => ['phenix' => ['dont-discover' => ['acme/blog']]]], JSON_THROW_ON_ERROR));

    expect((new PackageManifest($this->packageApp))->providers())->toBe([]);

    file_put_contents($composer, '{}');
    file_put_contents($installed, '{"packages":[]}');

    expect((new PackageManifest($this->packageApp))->providers())->toBe([]);
});

it('rebuilds invalid and legacy manifests', function (): void {
    $path = "{$this->packageApp}/storage/framework/cache/packages.php";

    file_put_contents($path, '<?php this is invalid');

    expect((new PackageManifest($this->packageApp))->providers())->toBe([]);

    file_put_contents($path, "<?php return ['old' => ['providers' => ['MissingProvider']]];");

    expect((new PackageManifest($this->packageApp))->providers())->toBe([]);
});
