<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Facades\Config;
use Phenix\Packages\PackageRegistry;
use Tests\Unit\Providers\Internal\PackageServiceProvider;

beforeEach(function (): void {
    $this->providerPath = base_path('storage/framework/testing/service-provider-' . uniqid());
    $this->provider = new PackageServiceProvider();

    mkdir($this->providerPath, 0755, true);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->providerPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->providerPath);
});

it('merges package configuration without replacing application values', function (): void {
    $config = "{$this->providerPath}/package.php";
    file_put_contents($config, "<?php return ['enabled' => true, 'driver' => 'package'];");
    Config::set('package-coverage', ['driver' => 'application']);

    $this->provider->mergePackageConfig($config, 'package-coverage');

    expect(Config::get('package-coverage'))->toBe([
        'enabled' => true,
        'driver' => 'application',
    ]);
});

it('ignores package configuration files that do not return arrays', function (): void {
    $config = "{$this->providerPath}/invalid.php";
    file_put_contents($config, "<?php return 'invalid';");

    $this->provider->mergePackageConfig($config, 'invalid-package-coverage');

    expect(Config::has('invalid-package-coverage'))->toBeFalse();
});

it('loads an existing package routes file', function (): void {
    $routes = "{$this->providerPath}/routes.php";
    $marker = "{$this->providerPath}/routes-loaded";
    file_put_contents($routes, '<?php file_put_contents(' . var_export($marker, true) . ", 'loaded');");

    $this->provider->loadPackageRoutes($routes);

    expect($marker)->toBeFile()
        ->and(file_get_contents($marker))->toBe('loaded');
});

it('ignores a missing package routes file', function (): void {
    $this->provider->loadPackageRoutes("{$this->providerPath}/missing.php");

    expect($this->providerPath)->toBeDirectory();
});

it('registers package resource paths', function (): void {
    $this->provider->loadPackageMigrations(['/package/migrations', '/package/migrations-extra']);
    $this->provider->loadPackageViews('/package/views', 'blog');
    $this->provider->loadPackageTranslations('/package/lang', 'blog');
    $this->provider->publishPackagePaths([
        '/package/config.php' => '/app/config.php',
    ], 'config');
    $this->provider->publishPackageMigrations([
        '/package/migrations/create_posts.php' => '/app/migrations/create_posts.php',
    ], 'migrations');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);

    expect($packages->migrationPaths())->toBe(['/package/migrations', '/package/migrations-extra'])
        ->and($packages->viewPaths('blog'))->toBe(['/package/views'])
        ->and($packages->translationPaths())->toBe(['blog' => ['/package/lang']])
        ->and($packages->pathsToPublish(PackageServiceProvider::class))->toBe([
            '/package/config.php' => '/app/config.php',
            '/package/migrations/create_posts.php' => '/app/migrations/create_posts.php',
        ])
        ->and($packages->pathsToPublish(group: 'config'))->toBe([
            '/package/config.php' => '/app/config.php',
        ])
        ->and($packages->pathsToPublish(group: 'migrations'))->toBe([
            '/package/migrations/create_posts.php' => '/app/migrations/create_posts.php',
        ]);
});
