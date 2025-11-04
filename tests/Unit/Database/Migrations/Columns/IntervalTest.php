<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Interval;

it('can create interval column', function (): void {
    $column = new Interval('duration');

    expect($column->getName())->toBe('duration');
    expect($column->getType())->toBe('interval');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Interval('duration');
    $column->default('1 hour');

    expect($column->getOptions()['default'])->toBe('1 hour');
});

it('can be nullable', function (): void {
    $column = new Interval('processing_time');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Interval('duration');
    $column->comment('Event duration');

    expect($column->getOptions()['comment'])->toBe('Event duration');
});
