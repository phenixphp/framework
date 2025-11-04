<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Cidr;

it('can create cidr column', function (): void {
    $column = new Cidr('network');

    expect($column->getName())->toBe('network');
    expect($column->getType())->toBe('cidr');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Cidr('network');
    $column->default('192.168.0.0/24');

    expect($column->getOptions()['default'])->toBe('192.168.0.0/24');
});

it('can be nullable', function (): void {
    $column = new Cidr('subnet');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Cidr('network');
    $column->comment('Network CIDR block');

    expect($column->getOptions()['comment'])->toBe('Network CIDR block');
});
