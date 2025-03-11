<?php

declare(strict_types=1);

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
