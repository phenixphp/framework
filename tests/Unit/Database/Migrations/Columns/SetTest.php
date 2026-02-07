<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Set;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\AdapterWrapper;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\PostgresAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;

beforeEach(function (): void {
    $this->mockAdapter = $this->getMockBuilder(AdapterInterface::class)->getMock();

    $this->mockMysqlAdapter = $this->getMockBuilder(MysqlAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $this->mockPostgresAdapter = $this->getMockBuilder(PostgresAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();

    $this->mockSQLiteAdapter = $this->getMockBuilder(SQLiteAdapter::class)
        ->disableOriginalConstructor()
        ->getMock();
});

it('can create set column with values', function (): void {
    $column = new Set('permissions', ['read', 'write', 'execute']);

    expect($column->getName())->toBe('permissions');
    expect($column->getType())->toBe('set');
    expect($column->getOptions())->toBe([
        'null' => false,
        'values' => ['read', 'write', 'execute'],
    ]);
});

it('can set default value as string', function (): void {
    $column = new Set('status', ['active', 'inactive']);
    $column->default('active');

    expect($column->getOptions()['default'])->toBe('active');
});

it('can set default value as array', function (): void {
    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->default(['read', 'write']);

    expect($column->getOptions()['default'])->toBe(['read', 'write']);
});

it('can update values', function (): void {
    $column = new Set('permissions', ['read', 'write']);
    $column->values(['read', 'write', 'execute', 'admin']);

    expect($column->getOptions()['values'])->toBe(['read', 'write', 'execute', 'admin']);
});

it('can set collation for mysql', function (): void {
    $column = new Set('status', ['active', 'inactive']);
    $column->setAdapter($this->mockMysqlAdapter);
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions()['collation'])->toBe('utf8mb4_unicode_ci');
});

it('ignores collation for non-mysql adapters', function (): void {
    $column = new Set('status', ['active', 'inactive']);
    $column->setAdapter($this->mockPostgresAdapter);
    $column->collation('utf8mb4_unicode_ci');

    expect($column->getOptions())->not->toHaveKey('collation');
});

it('can set encoding for mysql', function (): void {
    $column = new Set('status', ['active', 'inactive']);
    $column->setAdapter($this->mockMysqlAdapter);
    $column->encoding('utf8mb4');

    expect($column->getOptions()['encoding'])->toBe('utf8mb4');
});

it('ignores encoding for non-mysql adapters', function (): void {
    $column = new Set('status', ['active', 'inactive']);
    $column->setAdapter($this->mockPostgresAdapter);
    $column->encoding('utf8mb4');

    expect($column->getOptions())->not->toHaveKey('encoding');
});

it('can be nullable', function (): void {
    $column = new Set('permissions', ['read', 'write']);
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Set('permissions', ['read', 'write']);
    $column->comment('User permissions');

    expect($column->getOptions()['comment'])->toBe('User permissions');
});

it('returns string type for SQLite adapter', function (): void {
    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->setAdapter($this->mockSQLiteAdapter);

    expect($column->getType())->toBe('string');
});

it('returns set type for MySQL adapter', function (): void {
    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->setAdapter($this->mockMysqlAdapter);

    expect($column->getType())->toBe('set');
});

it('returns set type for PostgreSQL adapter', function (): void {
    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->setAdapter($this->mockPostgresAdapter);

    expect($column->getType())->toBe('set');
});

it('returns string type for SQLite wrapped in AdapterWrapper', function (): void {
    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($this->mockSQLiteAdapter);

    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->setAdapter($wrapper);

    expect($column->getType())->toBe('string');
});

it('returns set type for MySQL wrapped in AdapterWrapper', function (): void {
    $wrapper = $this->getMockBuilder(AdapterWrapper::class)
        ->disableOriginalConstructor()
        ->getMock();

    $wrapper->expects($this->any())
        ->method('getAdapter')
        ->willReturn($this->mockMysqlAdapter);

    $column = new Set('permissions', ['read', 'write', 'execute']);
    $column->setAdapter($wrapper);

    expect($column->getType())->toBe('set');
});
