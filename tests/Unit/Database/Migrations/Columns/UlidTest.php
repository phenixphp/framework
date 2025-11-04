<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Ulid;

it('can create ulid column', function (): void {
    $column = new Ulid('ulid_field');

    expect($column->getName())->toBe('ulid_field');
    expect($column->getType())->toBe('string');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 26,
    ]);
});

it('can be nullable', function (): void {
    $column = new Ulid('ulid_field');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can set default value', function (): void {
    $defaultUlid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $column = new Ulid('ulid_field');
    $column->default($defaultUlid);

    expect($column->getOptions()['default'])->toBe($defaultUlid);
});

it('can have comment', function (): void {
    $column = new Ulid('ulid_field');
    $column->comment('User identifier');

    expect($column->getOptions()['comment'])->toBe('User identifier');
});

it('maintains fixed length of 26 characters when limit is called', function (): void {
    $column = new Ulid('ulid_field');
    $column->limit(50);

    expect($column->getOptions()['limit'])->toBe(26);
});

it('maintains fixed length of 26 characters when length is called', function (): void {
    $column = new Ulid('ulid_field');
    $column->length(100);

    expect($column->getOptions()['limit'])->toBe(26);
});
