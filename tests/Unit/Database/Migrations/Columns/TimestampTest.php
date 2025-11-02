<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Timestamp;

it('can create timestamp column without timezone', function (): void {
    $column = new Timestamp('created_at');

    expect($column->getName())->toBe('created_at');
    expect($column->getType())->toBe('timestamp');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can create timestamp column with timezone', function (): void {
    $column = new Timestamp('created_at', true);

    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => true,
    ]);
});

it('can set default value', function (): void {
    $column = new Timestamp('created_at');
    $column->default('2023-01-01 12:00:00');

    expect($column->getOptions()['default'])->toBe('2023-01-01 12:00:00');
});

it('can set timezone', function (): void {
    $column = new Timestamp('created_at');
    $column->timezone(true);

    expect($column->getOptions()['timezone'])->toBeTrue();
});

it('can disable timezone', function (): void {
    $column = new Timestamp('created_at', true);
    $column->timezone(false);

    expect($column->getOptions()['timezone'])->toBeFalse();
});

it('can set update action', function (): void {
    $column = new Timestamp('updated_at');
    $column->update('CURRENT_TIMESTAMP');

    expect($column->getOptions()['update'])->toBe('CURRENT_TIMESTAMP');
});

it('can use current timestamp as default', function (): void {
    $column = new Timestamp('created_at');
    $column->currentTimestamp();

    expect($column->getOptions()['default'])->toBe('CURRENT_TIMESTAMP');
});

it('can use on update current timestamp', function (): void {
    $column = new Timestamp('updated_at');
    $column->onUpdateCurrentTimestamp();

    expect($column->getOptions()['update'])->toBe('CURRENT_TIMESTAMP');
});

it('can be nullable', function (): void {
    $column = new Timestamp('deleted_at');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Timestamp('created_at');
    $column->comment('Creation timestamp');

    expect($column->getOptions()['comment'])->toBe('Creation timestamp');
});
