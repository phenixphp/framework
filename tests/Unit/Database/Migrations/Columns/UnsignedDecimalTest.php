<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\UnsignedDecimal;

it('can create unsigned decimal column with default precision and scale', function (): void {
    $column = new UnsignedDecimal('price');

    expect($column->getName())->toBe('price');
    expect($column->getType())->toBe('decimal');
    expect($column->getOptions())->toBe([
        'null' => false,
        'precision' => 10,
        'scale' => 2,
        'signed' => false,
    ]);
});

it('can create unsigned decimal column with custom precision and scale', function (): void {
    $column = new UnsignedDecimal('amount', 15, 4);

    expect($column->getOptions()['precision'])->toBe(15);
    expect($column->getOptions()['scale'])->toBe(4);
});

it('can set default value', function (): void {
    $column = new UnsignedDecimal('price');
    $column->default(99.99);

    expect($column->getOptions()['default'])->toBe(99.99);
});

it('can set precision', function (): void {
    $column = new UnsignedDecimal('price');
    $column->precision(12);

    expect($column->getOptions()['precision'])->toBe(12);
});

it('can set scale', function (): void {
    $column = new UnsignedDecimal('price');
    $column->scale(4);

    expect($column->getOptions()['scale'])->toBe(4);
});

it('can be nullable', function (): void {
    $column = new UnsignedDecimal('discount');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new UnsignedDecimal('price');
    $column->comment('Product price');

    expect($column->getOptions()['comment'])->toBe('Product price');
});
