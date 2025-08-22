<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\MysqlConnectionPool;

it('sets custom connection for database query builder', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::name('mysql'), MysqlConnectionPool::fake($data));

    $queryBuilder = new DatabaseQueryBuilder();
    $queryBuilder->connection('mysql');
    $queryBuilder->setModel(new User());

    $result = $queryBuilder->get()->toArray();

    expect($result[0]['id'])->toBe($data[0]['id']);
});

it('clone model query builder successfully', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $this->app->swap(Connection::name('mysql'), $connection);

    $queryBuilder = new DatabaseQueryBuilder();
    $queryBuilder->connection('mysql');
    $queryBuilder->lockForUpdate();

    $cloned = clone $queryBuilder;

    expect($cloned)->toBeInstanceOf(DatabaseQueryBuilder::class);
    expect($cloned->isLocked())->toBeFalse();
});
