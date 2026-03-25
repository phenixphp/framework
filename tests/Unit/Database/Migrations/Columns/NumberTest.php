<?php

declare(strict_types=1);

use Tests\Unit\Database\Migrations\Columns\Internal\TestNumber;

it('can set default value', function (): void {
    $column = new TestNumber('test');
    $column->default(42);

    expect($column->getOptions()['default'])->toBe(42);
});

it('can set identity', function (): void {
    $column = new TestNumber('test');
    $column->identity();

    expect($column->getOptions()['identity'])->toBeTrue();
});

it('can be nullable', function (): void {
    $column = new TestNumber('test');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new TestNumber('test');
    $column->comment('Test number');

    expect($column->getOptions()['comment'])->toBe('Test number');
});
