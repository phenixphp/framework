<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Bit;

it('can create bit column with default limit', function (): void {
    $column = new Bit('flags');

    expect($column->getName())->toBe('flags');
    expect($column->getType())->toBe('bit');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 1,
    ]);
});

it('can create bit column with custom limit', function (): void {
    $column = new Bit('permissions', 8);

    expect($column->getOptions()['limit'])->toBe(8);
});

it('can set limit after creation', function (): void {
    $column = new Bit('flags');
    $column->limit(16);

    expect($column->getOptions()['limit'])->toBe(16);
});

it('throws exception when limit is less than 1', function (): void {
    $column = new Bit('flags');

    try {
        $column->limit(0);
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (\InvalidArgumentException $e) {
        expect($e->getMessage())->toBe('Bit limit must be between 1 and 64');
    }
});

it('throws exception when limit is greater than 64', function (): void {
    $column = new Bit('flags');

    try {
        $column->limit(65);
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (\InvalidArgumentException $e) {
        expect($e->getMessage())->toBe('Bit limit must be between 1 and 64');
    }
});

it('can set default value', function (): void {
    $column = new Bit('flags');
    $column->default(1);

    expect($column->getOptions()['default'])->toBe(1);
});

it('can be nullable', function (): void {
    $column = new Bit('flags');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Bit('flags');
    $column->comment('Status flags');

    expect($column->getOptions()['comment'])->toBe('Status flags');
});
