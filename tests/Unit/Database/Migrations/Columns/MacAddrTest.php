<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\MacAddr;

it('can create macaddr column', function (): void {
    $column = new MacAddr('mac_address');

    expect($column->getName())->toBe('mac_address');
    expect($column->getType())->toBe('macaddr');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can set default value', function (): void {
    $column = new MacAddr('mac_address');
    $column->default('08:00:2b:01:02:03');

    expect($column->getOptions()['default'])->toBe('08:00:2b:01:02:03');
});

it('can be nullable', function (): void {
    $column = new MacAddr('device_mac');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new MacAddr('mac_address');
    $column->comment('Device MAC address');

    expect($column->getOptions()['comment'])->toBe('Device MAC address');
});
