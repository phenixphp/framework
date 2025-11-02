<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Inet;

it('can create inet column', function (): void {
    $column = new Inet('ip_address');

    expect($column->getName())->toBe('ip_address');
    expect($column->getType())->toBe('inet');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new Inet('ip_address');
    $column->default('192.168.1.1');

    expect($column->getOptions()['default'])->toBe('192.168.1.1');
});

it('can be nullable', function (): void {
    $column = new Inet('client_ip');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Inet('ip_address');
    $column->comment('Client IP address');

    expect($column->getOptions()['comment'])->toBe('Client IP address');
});
