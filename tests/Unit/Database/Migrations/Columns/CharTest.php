<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Char;
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

it('can create char column with default limit', function (): void {
    $column = new Char('code');

    expect($column->getName())->toBe('code');
    expect($column->getType())->toBe('char');
    expect($column->getOptions())->toBe([
        'null' => false,
        'limit' => 255,
    ]);
});

it('can create char column with custom limit', function (): void {
    $column = new Char('status', 10);

    expect($column->getOptions()['limit'])->toBe(10);
});

it('can set limit after creation', function (): void {
    $column = new Char('code');
    $column->limit(50);

    expect($column->getOptions()['limit'])->toBe(50);
});

it('can set default value', function (): void {
    $column = new Char('status');
    $column->default('A');

    expect($column->getOptions()['default'])->toBe('A');
});

it('can set collation for mysql', function (): void {
    $column = new Char('code');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions()['collation'])->toBe('utf8mb4_unicode_ci');
});

it('ignores collation for non-mysql adapters', function (): void {
    $column = new Char('code');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions())->not->toHaveKey('collation');
});

it('can set encoding for mysql', function (): void {
    $column = new Char('code');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->encoding('utf8mb4');

    expect($column->getOptions()['encoding'])->toBe('utf8mb4');
});

it('ignores encoding for non-mysql adapters', function (): void {
    $column = new Char('code');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->encoding('utf8mb4');

    expect($column->getOptions())->not->toHaveKey('encoding');
});

it('can be nullable', function (): void {
    $column = new Char('code');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Char('code');
    $column->comment('Status code');

    expect($column->getOptions()['comment'])->toBe('Status code');
});
