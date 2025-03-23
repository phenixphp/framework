<?php

declare(strict_types=1);

use Phenix\Exceptions\Views\ViewNotFoundException;
use Phenix\Facades\File;
use Phenix\Views\TemplateEngine;
use Phenix\Views\ViewCache;

beforeEach(function () {
    $path = $this->getAppDir() . '/storage/framework/views';

    foreach (File::listFiles($path) as $file) {
        $filePath = "{$path}/{$file}";

        if (str_ends_with($filePath, '.php')) {
            File::deleteFile($filePath);
        }
    }
});

it('render a template successfully', function (): void {
    $template = new TemplateEngine();
    $output = $template->view('welcome', [
        'title' => 'Welcome',
        'colors' => ['red', 'green', 'blue'],
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Welcome');
    expect($output)->toContain('red');
    expect($output)->toContain('green');
    expect($output)->toContain('blue');
});

it('render a template in a specific directory successfully', function (): void {
    $template = new TemplateEngine();
    $output = $template->view('users.index', [
        'title' => 'Users',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Users');
});

it('render a template including partial', function (): void {
    $token = 'abcd123';

    $template = new TemplateEngine();
    $output = $template->view('users.create', [
        'title' => 'Create user',
        'token' => $token,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('form');
    expect($output)->toContain($token);
});

it('throw exception when template not found', function (): void {
    $template = new TemplateEngine();

    $template->view('missing')->render();
})->throws(ViewNotFoundException::class);

it('register custom directive', function (): void {
    $action = 'You can create it';

    $template = new TemplateEngine();
    $template->directive('can', function (string $action): string {
        return "<?php if({$action} === 'create'): ?>";
    });
    $template->directive('endcan', function (): string {
        return "<?php endif; ?>";
    });

    $output = $template->view('invoice', [
        'title' => 'Create invoices',
        'action' => $action,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain($action);
});

it('throw exception when template has errors', function (): void {
    $template = new TemplateEngine();

    $template->view('invalid_content')->render();
})->throws(Exception::class);

it('overwrite an expired template in cache', function (): void {
    // Precompile the view
    $template = new TemplateEngine();
    $output = $template->view('users.index', [
        'title' => 'Previous title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Previous title');

    $cache = new class () extends ViewCache {
        protected function isExpired(string $sourceFile, string $cacheFile): bool
        {
            return true;
        }
    };

    $template = new TemplateEngine(cache: $cache);

    $output = $template->view('users.index', [
        'title' => 'New title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('New title');
});
