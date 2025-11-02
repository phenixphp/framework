<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\DateTime;

it('can create datetime column', function (): void {
    $column = new DateTime('created_at');

    expect($column->getName())->toBe('created_at');
    expect($column->getType())->toBe('datetime');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new DateTime('published_at');
    $column->default('2023-01-01 12:00:00');

    expect($column->getOptions()['default'])->toBe('2023-01-01 12:00:00');
});

it('can be nullable', function (): void {
    $column = new DateTime('deleted_at');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new DateTime('created_at');
    $column->comment('Creation timestamp');

    expect($column->getOptions()['comment'])->toBe('Creation timestamp');
});
