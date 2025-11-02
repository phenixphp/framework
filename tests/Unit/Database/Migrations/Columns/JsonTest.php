<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Json;

it('can create json column', function (): void {
    $column = new Json('metadata');

    expect($column->getName())->toBe('metadata');
    expect($column->getType())->toBe('json');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Json('settings');
    $column->default('{}');

    expect($column->getOptions()['default'])->toBe('{}');
});

it('can be nullable', function (): void {
    $column = new Json('config');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Json('metadata');
    $column->comment('JSON metadata');

    expect($column->getOptions()['comment'])->toBe('JSON metadata');
});
