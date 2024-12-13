<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connections;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\MysqlConnectionPool;

it('sets custom connection for database query builder', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connections::name('mysql'), MysqlConnectionPool::fake($data));

    $queryBuilder = new DatabaseQueryBuilder();
    $queryBuilder->connection('mysql');
    $queryBuilder->setModel(new User());

    $result = $queryBuilder->get()->toArray();

    expect($result[0]['id'])->toBe($data[0]['id']);
});
