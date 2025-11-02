<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Time;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\PostgresAdapter;

beforeEach(function (): void {
    $this->mockAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

    $this->mockPostgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();
});

it('can create time column without timezone', function (): void {
    $column = new Time('start_time');

    expect($column->getName())->toBe('start_time');
    expect($column->getType())->toBe('time');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can create time column with timezone for postgres after setting adapter', function (): void {
    $column = new Time('start_time', true);
    $column->setAdapter($this->mockPostgresAdapter);
    $column->withTimezone(true);

    expect($column->getOptions())->toBe([
        'null' => false,
        'timezone' => true,
    ]);
});

it('can set default value', function (): void {
    $column = new Time('start_time');
    $column->default('09:00:00');

    expect($column->getOptions()['default'])->toBe('09:00:00');
});

it('can set timezone for postgres', function (): void {
    $column = new Time('start_time');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->withTimezone(true);

    expect($column->getOptions()['timezone'])->toBeTrue();
});

it('ignores timezone for non-postgres adapters', function (): void {
    $column = new Time('start_time');
    $column->setAdapter($this->mockAdapter);
    $column->withTimezone(true);

    expect($column->getOptions())->not->toHaveKey('timezone');
});

it('can be nullable', function (): void {
    $column = new Time('end_time');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Time('start_time');
    $column->comment('Event start time');

    expect($column->getOptions()['comment'])->toBe('Event start time');
});
