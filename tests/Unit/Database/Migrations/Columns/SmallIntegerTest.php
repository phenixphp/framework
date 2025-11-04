<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\SmallInteger;

it('can create small integer column with default settings', function (): void {
    $column = new SmallInteger('status');

    expect($column->getName())->toBe('status');
    expect($column->getType())->toBe('smallinteger');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can create small integer column with identity', function (): void {
    $column = new SmallInteger('id', true);

    expect($column->getOptions())->toBe([
        'null' => false,
        'identity' => true,
    ]);
});

it('can create small integer column as unsigned', function (): void {
    $column = new SmallInteger('count', false, false);

    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new SmallInteger('status');
    $column->default(1);

    expect($column->getOptions()['default'])->toBe(1);
});

it('can set identity', function (): void {
    $column = new SmallInteger('id');
    $column->identity();

    expect($column->getOptions()['identity'])->toBeTrue();
    expect($column->getOptions()['null'])->toBeFalse();
});

it('can be unsigned', function (): void {
    $column = new SmallInteger('count');
    $column->unsigned();

    expect($column->getOptions()['signed'])->toBeFalse();
});

it('can be signed', function (): void {
    $column = new SmallInteger('balance');
    $column->signed();

    expect($column->getOptions()['signed'])->toBeTrue();
});

it('can be nullable', function (): void {
    $column = new SmallInteger('priority');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new SmallInteger('status');
    $column->comment('Status code');

    expect($column->getOptions()['comment'])->toBe('Status code');
});
