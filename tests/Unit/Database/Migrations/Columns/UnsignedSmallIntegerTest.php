<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\UnsignedSmallInteger;

it('can create unsigned small integer column', function (): void {
    $column = new UnsignedSmallInteger('count');

    expect($column->getName())->toBe('count');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
    ]);
});

it('can create unsigned small integer column with identity', function (): void {
    $column = new UnsignedSmallInteger('id', true);

    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
        'identity' => true,
    ]);
});

it('can set default value', function (): void {
    $column = new UnsignedSmallInteger('status');
    $column->default(1);

    expect($column->getOptions()['default'])->toBe(1);
});

it('can set identity', function (): void {
    $column = new UnsignedSmallInteger('id');
    $column->identity();

    expect($column->getOptions()['identity'])->toBeTrue();
    expect($column->getOptions()['null'])->toBeFalse();
});

it('can be nullable', function (): void {
    $column = new UnsignedSmallInteger('priority');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new UnsignedSmallInteger('count');
    $column->comment('Item count');

    expect($column->getOptions()['comment'])->toBe('Item count');
});
