<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Double;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;

beforeEach(function (): void {
    $this->mockMysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $this->mockPostgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();
});

it('can create double column with default signed', function (): void {
    $column = new Double('value');

    expect($column->getName())->toBe('value');
    expect($column->getType())->toBe('double');
    expect($column->getOptions())->toBe([
        'null' => false,
        'signed' => true,
    ]);
});

it('can create double column as unsigned', function (): void {
    $column = new Double('value', false);

    expect($column->getOptions()['signed'])->toBeFalse();
});

it('can set default value as float', function (): void {
    $column = new Double('temperature');
    $column->default(98.6);

    expect($column->getOptions()['default'])->toBe(98.6);
});

it('can set default value as integer', function (): void {
    $column = new Double('count');
    $column->default(100);

    expect($column->getOptions()['default'])->toBe(100);
});

it('can set unsigned for mysql', function (): void {
    $column = new Double('value');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->unsigned();

    expect($column->getOptions()['signed'])->toBeFalse();
});

it('ignores unsigned for non-mysql adapters', function (): void {
    $column = new Double('value');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->unsigned();

    expect($column->getOptions()['signed'])->toBeTrue();
});

it('can set signed for mysql', function (): void {
    $column = new Double('value', false);
    $column->setAdapter($this->mockMysqlAdapter);
    $column->signed();

    expect($column->getOptions()['signed'])->toBeTrue();
});

it('ignores signed for non-mysql adapters', function (): void {
    $column = new Double('value', false);
    $column->setAdapter($this->mockPostgresAdapter);
    $column->signed();

    expect($column->getOptions()['signed'])->toBeFalse();
});

it('can be nullable', function (): void {
    $column = new Double('measurement');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Double('value');
    $column->comment('Measurement value');

    expect($column->getOptions()['comment'])->toBe('Measurement value');
});
