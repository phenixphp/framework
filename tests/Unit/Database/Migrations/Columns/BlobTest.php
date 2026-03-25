<?php

declare(strict_types=1);

use Phenix\Database\Migrations\Columns\Blob;
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

it('can create blob column without limit', function (): void {
    $column = new Blob('data');

    expect($column->getName())->toBe('data');
    expect($column->getType())->toBe('blob');
    expect($column->getOptions())->toBe([
        'null' => false,
    ]);
});

it('can create blob column with limit', function (): void {
    $column = new Blob('file_data', 1024);

    expect($column->getOptions()['limit'])->toBe(1024);
});

it('can set limit after creation', function (): void {
    $column = new Blob('data');
    $column->limit(2048);

    expect($column->getOptions()['limit'])->toBe(2048);
});

it('can set tiny blob for mysql', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->tiny();

    expect($column->getOptions()['limit'])->toBe(MysqlAdapter::BLOB_TINY);
});

it('ignores tiny blob for non-mysql adapters', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->tiny();

    expect($column->getOptions())->not->toHaveKey('limit');
});

it('can set regular blob for mysql', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->regular();

    expect($column->getOptions()['limit'])->toBe(MysqlAdapter::BLOB_REGULAR);
});

it('ignores regular blob for non-mysql adapters', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->regular();

    expect($column->getOptions())->not->toHaveKey('limit');
});

it('can set medium blob for mysql', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->medium();

    expect($column->getOptions()['limit'])->toBe(MysqlAdapter::BLOB_MEDIUM);
});

it('ignores medium blob for non-mysql adapters', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->medium();

    expect($column->getOptions())->not->toHaveKey('limit');
});

it('can set long blob for mysql', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockMysqlAdapter);
    $column->long();

    expect($column->getOptions()['limit'])->toBe(MysqlAdapter::BLOB_LONG);
});

it('ignores long blob for non-mysql adapters', function (): void {
    $column = new Blob('data');
    $column->setAdapter($this->mockPostgresAdapter);
    $column->long();

    expect($column->getOptions())->not->toHaveKey('limit');
});

it('can be nullable', function (): void {
    $column = new Blob('data');
    $column->nullable();

    expect($column->getOptions()['null'])->toBeTrue();
});

it('can have comment', function (): void {
    $column = new Blob('data');
    $column->comment('Binary data');

    expect($column->getOptions()['comment'])->toBe('Binary data');
});
