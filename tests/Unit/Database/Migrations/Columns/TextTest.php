<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Text;

it('can create text column without limit', function (): void {
    $column = new Text('content');

    expect($column->getName())->toBe('content');
    expect($column->getType())->toBe('text');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can create text column with limit', function (): void {
    $column = new Text('description', 1000);

    expect($column->getOptions()['limit'])->toBe(1000);
});

it('can set default value', function (): void {
    $column = new Text('content');
    $column->default('Default content');

    expect($column->getOptions()['default'])->toBe('Default content');
});

it('can set collation', function (): void {
    $column = new Text('content');
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions()['collation'])->toBe('utf8mb4_unicode_ci');
});

it('can set encoding', function (): void {
    $column = new Text('content');
    $column->encoding('utf8mb4');

    expect($column->getOptions()['encoding'])->toBe('utf8mb4');
});

it('can be nullable', function (): void {
    $column = new Text('notes');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Text('content');
    $column->comment('Post content');

    expect($column->getOptions()['comment'])->toBe('Post content');
});

it('can set limit after creation', function (): void {
    $column = new Text('content');
    $column->limit(2000);

    expect($column->getOptions()['limit'])->toBe(2000);
});
