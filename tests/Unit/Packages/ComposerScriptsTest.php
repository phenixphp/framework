<?php

declare(strict_types=1);

use Phenix\Packages\ComposerScripts;

beforeEach(function (): void {
    $this->originalDirectory = getcwd();
    $this->composerApp = base_path('storage/framework/testing/composer-scripts-' . uniqid());

    mkdir("{$this->composerApp}/storage/framework/cache", 0755, true);
    chdir($this->composerApp);
});

afterEach(function (): void {
    chdir($this->originalDirectory);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->composerApp, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }

    rmdir($this->composerApp);
});

it('removes the cached package manifest after an autoload dump', function (): void {
    $manifest = "{$this->composerApp}/storage/framework/cache/packages.php";
    file_put_contents($manifest, '<?php return [];');

    ComposerScripts::postAutoloadDump();

    expect($manifest)->not->toBeFile();
});

it('does nothing when the cached package manifest does not exist', function (): void {
    ComposerScripts::postAutoloadDump();

    expect("{$this->composerApp}/storage/framework/cache")->toBeDirectory();
});
