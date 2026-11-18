<?php

declare(strict_types=1);

use Phenix\Packages\PackageRegistry;

it('registers package resources and publication groups', function (): void {
    $packages = new PackageRegistry();
    $packages->addMigrationPaths(['/package/migrations', '/package/migrations']);
    $packages->addViewPaths('/package/views', 'blog');
    $packages->addTranslationPath('/package/lang', 'blog');
    $packages->addPublishPaths('BlogProvider', [
        '/package/config.php' => '/app/config/blog.php',
    ], 'blog-config');

    expect($packages->migrationPaths())->toBe(['/package/migrations'])
        ->and($packages->viewPaths('blog'))->toBe(['/package/views'])
        ->and($packages->translationPaths())->toBe(['blog' => ['/package/lang']])
        ->and($packages->pathsToPublish('BlogProvider'))->toHaveKey('/package/config.php')
        ->and($packages->pathsToPublish(group: 'blog-config'))->toHaveKey('/package/config.php');
});
