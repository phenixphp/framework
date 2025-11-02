<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Date;

it('can create date column', function (): void {
    $column = new Date('birth_date');

    expect($column->getName())->toBe('birth_date');
    expect($column->getType())->toBe('date');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Date('created_date');
    $column->default('2023-01-01');

    expect($column->getOptions()['default'])->toBe('2023-01-01');
});

it('can be nullable', function (): void {
    $column = new Date('deleted_at');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Date('birth_date');
    $column->comment('User birth date');

    expect($column->getOptions()['comment'])->toBe('User birth date');
});
