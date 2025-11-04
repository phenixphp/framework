<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\JsonB;

it('can create jsonb column', function (): void {
    $column = new JsonB('metadata');

    expect($column->getName())->toBe('metadata');
    expect($column->getType())->toBe('jsonb');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value as string', function (): void {
    $column = new JsonB('settings');
    $column->default('{}');

    expect($column->getOptions()['default'])->toBe('{}');
});

it('can set default value as array', function (): void {
    $column = new JsonB('config');
    $column->default(['key' => 'value']);

    expect($column->getOptions()['default'])->toBe(['key' => 'value']);
});

it('can be nullable', function (): void {
    $column = new JsonB('data');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new JsonB('metadata');
    $column->comment('JSONB metadata');

    expect($column->getOptions()['comment'])->toBe('JSONB metadata');
});
