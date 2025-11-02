<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Decimal;

it('can create decimal column with default precision and scale', function (): void {
    $column = new Decimal('price');

    expect($column->getName())->toBe('price');
    expect($column->getType())->toBe('decimal');
    expect($column->getOptions())->toBe([
        'null' => false,
        'precision' => 10,
        'scale' => 2,
        'signed' => true,
    ]);
});

it('can create decimal column with custom precision and scale', function (): void {
    $column = new Decimal('amount', 15, 4);

    expect($column->getOptions()['precision'])->toBe(15);
    expect($column->getOptions()['scale'])->toBe(4);
});

it('can set default value', function (): void {
    $column = new Decimal('price');
    $column->default(99.99);

    expect($column->getOptions()['default'])->toBe(99.99);
});

it('can set precision', function (): void {
    $column = new Decimal('price');
    $column->precision(12);

    expect($column->getOptions()['precision'])->toBe(12);
});

it('can set scale', function (): void {
    $column = new Decimal('price');
    $column->scale(4);

    expect($column->getOptions()['scale'])->toBe(4);
});

it('can be unsigned', function (): void {
    $column = new Decimal('price');
    $column->unsigned();

    expect($column->getOptions()['signed'])->toBeFalse();
});

it('can be signed', function (): void {
    $column = new Decimal('balance');
    $column->signed();

    expect($column->getOptions()['signed'])->toBeTrue();
});

it('can be nullable', function (): void {
    $column = new Decimal('discount');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Decimal('price');
    $column->comment('Product price');

    expect($column->getOptions()['comment'])->toBe('Product price');
});
