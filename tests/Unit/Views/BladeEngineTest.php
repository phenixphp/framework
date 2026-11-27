<?php

declare(strict_types=1);

use Phenix\Views\BladeEngine;
use Phenix\Views\Exceptions\ViewNotFoundException;
use Phenix\Views\TemplateCache;
use Phenix\Views\ViewsConfig;

it('compiles fast includes when needed and reuses their compiled content', function (): void {
    $config = new ViewsConfig();
    $name = 'blade-fast-include-test';
    $source = $config->path($name);
    $compiled = $config->cachePath($name);
    $engine = new BladeEngine(new TemplateCache());

    file_put_contents($source, 'Original {{ $name }}');

    try {
        $first = $engine->compileString("@includeFast('{$name}')");

        expect($first)->toContain('Original', 'e($name)');
        expect(is_file($compiled))->toBeTrue();

        file_put_contents($source, 'Updated {{ $name }}');

        $second = $engine->compileString("@includeFast('{$name}')");

        expect($second)->toBe($first)->not->toContain('Updated');
    } finally {
        unlink($source);

        if (is_file($compiled)) {
            unlink($compiled);
        }
    }
});

it('detects missing, current and outdated compiled views', function (): void {
    $config = new ViewsConfig();
    $name = 'blade-expiration-test';
    $source = $config->path($name);
    $compiled = $config->cachePath($name);
    $engine = new BladeEngine(new TemplateCache());

    file_put_contents($source, 'View');

    try {
        expect($engine->isExpired($name))->toBeTrue();

        $engine->compile($name);
        touch($source, time() - 20);
        touch($compiled, time() - 10);

        expect($engine->isExpired($name))->toBeFalse();

        touch($source, time());

        expect($engine->isExpired($name))->toBeTrue();
    } finally {
        unlink($source);

        if (is_file($compiled)) {
            unlink($compiled);
        }
    }
});

it('reports a missing source when checking expiration', function (): void {
    $engine = new BladeEngine(new TemplateCache());

    expect(fn () => $engine->isExpired('missing-expiration-test'))->toThrow(ViewNotFoundException::class);
});
