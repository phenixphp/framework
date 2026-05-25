<?php

declare(strict_types=1);

use Phenix\Facades\View;
use Phenix\Views\Exceptions\ViewNotFoundException;
use Phenix\Views\TemplateCache;
use Phenix\Views\TemplateEngine;
use Phenix\Views\TemplateFactory;

it('render a template successfully', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

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
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => 'Users',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Users');
});

it('render a template including partial', function (): void {
    $token = 'abcd123';

    $template = new TemplateEngine();
    $template->clearCache();

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
    $template->clearCache();

    $template->view('missing')->render();
})->throws(ViewNotFoundException::class);

it('register custom directive', function (): void {
    $action = 'You can create it';

    $template = new TemplateEngine();
    $template->clearCache();

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
    $template->clearCache();

    $template->view('invalid_content')->render();
})->throws(Exception::class);

it('overwrite an expired template in cache', function (): void {
    // Precompile the view
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => 'Previous title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Previous title');

    $template = new TemplateEngine();

    $output = $template->view('users.index', [
        'title' => 'New title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('New title');
});

it('escapes XSS in inline section value', function (): void {
    $template = new TemplateEngine();
    $template->clearCache();

    $output = $template->view('users.index', [
        'title' => '<script>alert("xss")</script>',
    ])->render();

    expect($output)->toBeString();
    expect($output)->not->toContain('<script>');
    expect($output)->toContain('&lt;script&gt;');
});

it('escapes inline section values in template factory', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('title', '<script>alert("xss")</script>');

    expect($factory->yieldSection('title'))
        ->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
});

it('preserves rendered block section html in template factory', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('content');
    echo '<h1>Users</h1>';
    $factory->endSection();

    expect($factory->yieldSection('content'))->toBe('<h1>Users</h1>');
});

it('stores empty and zero inline section values without starting a buffer', function (): void {
    $factory = new TemplateFactory(new TemplateCache());

    $factory->startSection('empty', '');
    $factory->startSection('zero', '0');

    expect($factory->yieldSection('empty'))->toBe('');
    expect($factory->yieldSection('zero'))->toBe('0');
});

it('render template using facade', function (): void {
    View::clearCache();

    $output = View::view('users.index', [
        'title' => 'New title',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('New title');
});

it('register custom directive using facade', function (): void {
    $action = 'You can create it';

    View::clearCache();

    View::directive('can', function (string $action): string {
        return "<?php if({$action} === 'create'): ?>";
    });

    View::directive('endcan', function (): string {
        return "<?php endif; ?>";
    });

    $output = View::view('invoice', [
        'title' => 'Create invoices',
        'action' => $action,
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain($action);
});
