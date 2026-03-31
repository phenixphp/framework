<?php

declare(strict_types=1);

use Phenix\Database\Constants\Connection;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Facades\Config;
use Tests\Feature\Database\Models\User;
use Tests\Mocks\Database\PostgresqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;

use function Pest\Faker\faker;

it('saves a new model on postgresql using its mapped key column', function (): void {
    Config::set('database.default', Driver::POSTGRESQL->value);

    $capturedSql = '';
    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(function (string $sql) use (&$capturedSql): Statement {
            $capturedSql = $sql;

            $result = new Result([['user_id' => 77]]);
            $result->setLastInsertedId(77);

            return new Statement($result);
        });

    $this->app->swap(Connection::name(Driver::POSTGRESQL->value), $connection);

    $model = new User();
    $model->setConnection(Driver::POSTGRESQL->value);
    $model->name = 'John Doe';
    $model->email = faker()->email();

    expect($model->save())->toBeTrue();
    expect($model->id)->toBe(77);
    expect($model->isExisting())->toBeTrue();
    expect($capturedSql)->toContain('RETURNING id');
});

it('creates a new model on postgresql using its mapped key column', function (): void {
    Config::set('database.default', Driver::POSTGRESQL->value);

    $capturedSql = '';
    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(function (string $sql) use (&$capturedSql): Statement {
            $capturedSql = $sql;

            return new Statement(new Result([['user_id' => 88]]));
        });

    $queryBuilder = new DatabaseQueryBuilder();
    $queryBuilder->connection($connection);
    $queryBuilder->setModel(new User());

    $model = $queryBuilder->create([
        'name' => 'Jane Doe',
        'email' => faker()->email(),
    ]);

    expect($model->id)->toBe(88);
    expect($model->isExisting())->toBeTrue();
    expect($capturedSql)->toContain('RETURNING id');
});
