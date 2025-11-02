<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Uuid;

it('can create uuid column', function (): void {
    $column = new Uuid('uuid');

    expect($column->getName())->toBe('uuid');
    expect($column->getType())->toBe('uuid');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Uuid('identifier');
    $column->default('550e8400-e29b-41d4-a716-446655440000');

    expect($column->getOptions()['default'])->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('can be nullable', function (): void {
    $column = new Uuid('external_id');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Uuid('uuid');
    $column->comment('Unique identifier');

    expect($column->getOptions()['comment'])->toBe('Unique identifier');
});
