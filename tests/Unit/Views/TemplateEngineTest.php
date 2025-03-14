<?php

declare(strict_types=1);

use Phenix\Exceptions\Views\ViewNotFoundException;
use Phenix\Facades\File;
use Phenix\Views\TemplateEngine;

beforeEach(function () {
    $path = $this->getAppDir() . '/storage/framework/views';

    foreach (File::listFiles($path) as $file) {
        $filePath = "{$path}/{$file}";

        if (str_ends_with($filePath, '.php')) {
            File::deleteFile($filePath);
        }
    }
});

it('render a template successfully', function () {
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

it('render a template in a specific directory successfully', function () {
    $template = new TemplateEngine();
    $output = $template->view('users.index', [
        'title' => 'Users',
    ])->render();

    expect($output)->toBeString();
    expect($output)->toContain('Users');
});

it('render a template including partial', function () {
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

it('throw exception when template not found', function () {
    $template = new TemplateEngine();

    $template->view('missing')->render();
})->throws(ViewNotFoundException::class);
