<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Enum;

it('can create enum column with values', function (): void {
    $column = new Enum('status', ['active', 'inactive', 'pending']);

    expect($column->getName())->toBe('status');
    expect($column->getType())->toBe('enum');
    expect($column->getOptions())->toBe([
        'null' => false,
        'values' => ['active', 'inactive', 'pending'],
    ]);
});

it('can set default value', function (): void {
    $column = new Enum('status', ['active', 'inactive']);
    $column->default('active');

    expect($column->getOptions()['default'])->toBe('active');
});

it('can update values', function (): void {
    $column = new Enum('role', ['user', 'admin']);
    $column->values(['user', 'admin', 'moderator']);

    expect($column->getOptions()['values'])->toBe(['user', 'admin', 'moderator']);
});

it('can be nullable', function (): void {
    $column = new Enum('status', ['active', 'inactive']);
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Enum('status', ['active', 'inactive']);
    $column->comment('User status');

    expect($column->getOptions()['comment'])->toBe('User status');
});
