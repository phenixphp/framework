<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Packages\PackageRegistry;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->publishPath = base_path('storage/framework/testing/vendor-publish-' . uniqid());

    mkdir($this->publishPath, 0755, true);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->publishPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->publishPath);
});

it('publishes tagged package files', function (): void {
    $source = "{$this->publishPath}/package-source.php";
    $destination = "{$this->publishPath}/package-destination.php";
    file_put_contents($source, '<?php return [];');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('TestProvider', [$source => $destination], 'test-config');

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--tag' => 'test-config']);

    $command->assertCommandIsSuccessful();

    expect($destination)->toBeFile()
        ->and(file_get_contents($destination))->toBe('<?php return [];');
});

it('requires a publication selector', function (): void {
    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish');

    expect($command->getStatusCode())->toBe(1)
        ->and($command->getDisplay())->toContain('Specify --provider, --tag, or --all.');
});

it('reports when no publishable resources are registered', function (): void {
    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--all' => true]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('No publishable resources were found.');
});

it('publishes package directories recursively', function (): void {
    $source = "{$this->publishPath}/package/views";
    $destination = "{$this->publishPath}/resources/views/vendor/blog";
    mkdir("{$source}/posts", 0755, true);
    file_put_contents("{$source}/posts/index.php", '<h1>Posts</h1>');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('BlogProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'BlogProvider']);

    $command->assertCommandIsSuccessful();

    expect("{$destination}/posts/index.php")->toBeFile()
        ->and(file_get_contents("{$destination}/posts/index.php"))->toBe('<h1>Posts</h1>');
});

it('ignores non-file entries while publishing package directories', function (): void {
    $source = "{$this->publishPath}/package/views";
    $destination = "{$this->publishPath}/resources/views/vendor/blog";
    mkdir($source, 0755, true);
    file_put_contents("{$source}/index.php", '<h1>Blog</h1>');
    symlink("{$source}/missing.php", "{$source}/broken-link.php");

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('BlogProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'BlogProvider']);

    $command->assertCommandIsSuccessful();

    expect("{$destination}/index.php")->toBeFile()
        ->and("{$destination}/broken-link.php")->not->toBeFile();
});

it('reports missing package publication sources', function (): void {
    $source = "{$this->publishPath}/missing.php";
    $destination = "{$this->publishPath}/published.php";

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('MissingProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'MissingProvider']);

    expect($command->getStatusCode())->toBe(1)
        ->and($command->getDisplay())->toContain("Publish source {$source} does not exist.")
        ->and($destination)->not->toBeFile();
});

it('preserves published files unless force is used', function (): void {
    $source = "{$this->publishPath}/source.php";
    $destination = "{$this->publishPath}/destination.php";
    file_put_contents($source, 'package');
    file_put_contents($destination, 'application');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('ConfigProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'ConfigProvider']);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Skipped:')
        ->and(file_get_contents($destination))->toBe('application');
});

it('overwrites published files when force is used', function (): void {
    $source = "{$this->publishPath}/source.php";
    $destination = "{$this->publishPath}/destination.php";
    file_put_contents($source, 'package');
    file_put_contents($destination, 'application');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('ConfigProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', [
        '--provider' => 'ConfigProvider',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Published:')
        ->and(file_get_contents($destination))->toBe('package');
});

it('reports when the publication directory cannot be created', function (): void {
    $source = "{$this->publishPath}/source.php";
    $blockedDirectory = "{$this->publishPath}/blocked";
    $destination = "{$blockedDirectory}/destination.php";
    file_put_contents($source, 'package');
    file_put_contents($blockedDirectory, 'not-a-directory');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('BlockedProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'BlockedProvider']);

    expect($command->getStatusCode())->toBe(1)
        ->and($command->getDisplay())->toContain("Unable to create publish directory {$blockedDirectory}.");
});

it('reports when a package file cannot be copied', function (): void {
    $source = "{$this->publishPath}/source.php";
    $destination = "{$this->publishPath}/destination";
    file_put_contents($source, 'package');
    mkdir($destination);

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('UnreadableProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'UnreadableProvider']);

    expect($command->getStatusCode())->toBe(1)
        ->and($command->getDisplay())->toContain("Unable to publish {$source} to {$destination}.");
});

it('replaces package migration timestamps when publishing', function (): void {
    $source = "{$this->publishPath}/package/migrations/20240101000000_create_posts.php";
    $destination = "{$this->publishPath}/database/migrations/20240101000000_create_posts.php";
    mkdir(dirname($source), 0755, true);
    file_put_contents($source, '<?php');

    /** @var PackageRegistry $packages */
    $packages = App::make(PackageRegistry::class);
    $packages->addPublishPaths('MigrationProvider', [$source => $destination]);

    /** @var CommandTester $command */
    $command = $this->phenix('vendor:publish', ['--provider' => 'MigrationProvider']);

    $command->assertCommandIsSuccessful();
    $published = glob("{$this->publishPath}/database/migrations/*_create_posts.php");

    expect($published)->toHaveCount(1)
        ->and(basename($published[0]))->toMatch('/^\d{14}_create_posts\.php$/')
        ->not->toBe('20240101000000_create_posts.php');
});
