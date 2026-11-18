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

it('lists publishable providers and groups', function (): void {
    $packages = new PackageRegistry();
    $packages->addPublishPaths('BlogProvider', [
        '/package/blog.php' => '/app/blog.php',
    ], 'blog');
    $packages->addPublishPaths('ShopProvider', [
        '/package/shop.php' => '/app/shop.php',
    ], ['shop', 'config']);

    expect($packages->publishableProviders())->toBe(['BlogProvider', 'ShopProvider'])
        ->and($packages->publishableGroups())->toBe(['blog', 'shop', 'config']);
});

it('returns all publishable paths', function (): void {
    $packages = new PackageRegistry();
    $packages->addPublishPaths('BlogProvider', [
        '/package/blog.php' => '/app/blog.php',
    ]);
    $packages->addPublishPaths('ShopProvider', [
        '/package/shop.php' => '/app/shop.php',
    ]);

    expect($packages->pathsToPublish())->toBe([
        '/package/blog.php' => '/app/blog.php',
        '/package/shop.php' => '/app/shop.php',
    ]);
});

it('filters publishable paths by provider and group', function (): void {
    $packages = new PackageRegistry();
    $packages->addPublishPaths('BlogProvider', [
        '/package/config.php' => '/app/config.php',
        '/package/views/index.php' => '/app/views/index.php',
    ], ['config', 'views']);
    $packages->addPublishPaths('ShopProvider', [
        '/shop/config.php' => '/app/shop.php',
    ], 'config');

    expect($packages->pathsToPublish('BlogProvider', 'config'))->toBe([
        '/package/config.php' => '/app/config.php',
        '/package/views/index.php' => '/app/views/index.php',
    ]);
});

it('returns no publishable paths when the registry is empty', function (): void {
    expect((new PackageRegistry())->pathsToPublish())->toBe([]);
});
