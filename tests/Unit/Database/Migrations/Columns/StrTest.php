<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Str;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;

beforeEach(function (): void {
    $this->mockAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

    $this->mockMysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $this->mockPostgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();
});

it('can create string column with default limit', function (): void {
    $column = new Str('name');

    expect($column->getName())->toBe('name');
    expect($column->getType())->toBe('string');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
    ]);
});

it('can create string column with custom limit', function (): void {
    $column = new Str('username', 100);

    expect($column->getOptions()['limit'])->toBe(100);
});

it('can set default value', function (): void {
    $column = new Str('status');
    $column->default('active');

    expect($column->getOptions()['default'])->toBe('active');
});

it('can set collation', function (): void {
    $column = new Str('name');
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions()['collation'])->toBe('utf8mb4_unicode_ci');
});

it('can set encoding', function (): void {
    $column = new Str('name');
    $column->encoding('utf8mb4');

    expect($column->getOptions()['encoding'])->toBe('utf8mb4');
});

it('can be nullable', function (): void {
    $column = new Str('description');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Str('name');
    $column->comment('User name');

    expect($column->getOptions()['comment'])->toBe('User name');
});

it('can set limit after creation', function (): void {
    $column = new Str('name');
    $column->limit(150);

    expect($column->getOptions()['limit'])->toBe(150);
});

it('can set length after creation', function (): void {
    $column = new Str('name');
    $column->length(200);

    expect($column->getOptions()['limit'])->toBe(200);
});
