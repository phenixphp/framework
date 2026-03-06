<?php

declare(strict_types=1);

use Amp\Sql\SqlQueryError;
use Amp\Sql\SqlTransaction;
use League\Uri\Http;
use Phenix\Data\Collection;
use Phenix\Database\Constants\Connection;
use Phenix\Database\Paginator;
use Phenix\Database\QueryBuilder;
use Phenix\Database\TransactionManager;
use Phenix\Facades\DB;
use Phenix\Facades\Url;
use Tests\Mocks\Database\MysqlConnectionPool;
use Tests\Mocks\Database\PostgresqlConnectionPool;
use Tests\Mocks\Database\Result;
use Tests\Mocks\Database\Statement;
use Phenix\Facades\Config;
use Phenix\Facades\Crypto;

beforeEach(function (): void {
    Config::set('app.key', Crypto::generateEncodedKey());
});

it('gets all records from database', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::default(), MysqlConnectionPool::fake($data));

    $query = new QueryBuilder();

    $result = $query->from('users')
        ->select(['id', 'name'])
        ->get();

    expect($result->toArray())->toBe($data);
});

it('gets all records from database using facade', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::default(), MysqlConnectionPool::fake($data));

    $result = DB::from('users')
        ->select(['id', 'name'])
        ->get();

    expect($result->toArray())->toBe($data);
});

it('gets the first record from database', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::default(), MysqlConnectionPool::fake($data));

    $query = new QueryBuilder();

    $result = $query->from('users')
        ->select(['id', 'name'])
        ->first();

    expect($result)->toBe($data[0]);
});

it('sets custom connection', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::name('mysql'), MysqlConnectionPool::fake($data));

    $result = DB::connection('mysql')
        ->from('users')
        ->select(['id', 'name'])
        ->get();

    expect($result->toArray())->toBe($data);
});

it('insert records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->insert(['name' => 'Tony']);

    expect($result)->toBeTrue();
});

it('fails on insert records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Duplicate name'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->insert(['name' => 'Tony']);

    expect($result)->toBeFalsy();
});

it('throws any error on insert records', function () {
    expect(function () {
        $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

        $connection->expects($this->any())
            ->method('prepare')
            ->willThrowException(new ErrorException('Any error'));

        $query = new QueryBuilder();
        $query->connection($connection);

        $query->table('users')->insert(['name' => 'Tony']);
    })->toThrow(ErrorException::class);
});

it('updates records', function () {
    $data = [
        ['id' => 1, 'name' => 'John Doe'],
    ];

    $this->app->swap(Connection::default(), MysqlConnectionPool::fake($data));

    $result = DB::from('users')
        ->whereEqual('id', 1)
        ->update(['name' => 'Tony']);

    expect($result)->toBeTrue();
});

it('fails on record update', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Duplicate name'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->from('users')
        ->whereEqual('id', 1)
        ->update(['name' => 'Tony']);

    expect($result)->toBeFalse();
});

it('counts all database records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 1]])),
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $count = $query->from('users')->count();

    expect($count)->toBe(1);
});

it('paginates the query results', function (): void {
    $data = [['id' => 1, 'name' => 'John Doe']];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(2))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['COUNT(*)' => 1]])),
            new Statement(new Result($data))
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $uri = Http::new(Url::to('users'));

    $paginator = $query->from('users')
        ->select(['id', 'name'])
        ->paginate($uri);

    expect($paginator)->toBeInstanceOf(Paginator::class);
    expect($paginator->toArray())->toBe([
        'path' => Url::to('users'),
        'current_page' => 1,
        'last_page' => 1,
        'per_page' => 15,
        'total' => 1,
        'first_page_url' => Url::to('users', ['page' => 1]),
        'last_page_url' => Url::to('users', ['page' => 1]),
        'prev_page_url' => null,
        'next_page_url' => null,
        'from' => 1,
        'to' => 1,
        'data' => $data,
        'links' => [
            [
                'url' => Url::to('users', ['page' => 1]),
                'label' => 1,
            ],
        ],
    ]);
});

it('checks if record exists', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['exists' => 1]])),
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('email', 'john.doe@email.com')
        ->exists();

    expect($result)->toBeTrue();
});

it('checks if record does not exist', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([['exists' => 0]])),
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('email', 'john.doe@email.com')
        ->doesntExist();

    expect($result)->toBeTrue();
});

it('deletes records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([])),
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('email', 'john.doe@email.com')
        ->delete();

    expect($result)->toBeTrue();
});

it('fails on record deletion', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Constraint integrity'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('email', 'john.doe@email.com')
        ->delete();

    expect($result)->toBeFalse();
});

it('gets all records from database without select columns', function () {
    $data = [['id' => 1, 'name' => 'John Doe']];

    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($data)),
        );

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->from('users')->get();

    expect($result->toArray())->toBe($data);
});

it('execute database transaction successfully', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $data = [['id' => 1, 'name' => 'John Doe']];

    $transaction->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result($data)),
        );

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $transaction->expects($this->once())
        ->method('commit');

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->transaction(function (TransactionManager $transactionManager): Collection {
        return $transactionManager->from('users')->get();
    });

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->toArray())->toBe($data);
});

it('rollback transaction on error', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $transaction->expects($this->exactly(1))
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Transaction failed'));

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $transaction->expects($this->once())
        ->method('rollback');

    $query = new QueryBuilder();
    $query->connection($connection);

    $query->transaction(function (TransactionManager $transactionManager): Collection {
        return $transactionManager->from('users')->get();
    });
})->throws(SqlQueryError::class);

it('execute transaction manually', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $transaction->expects($this->exactly(1))
        ->method('prepare')
        ->willReturnOnConsecutiveCalls(
            new Statement(new Result([])),
        );

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $transaction->expects($this->once())
        ->method('commit');

    $query = new QueryBuilder();
    $query->connection($connection);
    $query->beginTransaction();
    $query->from('users')->get();
    $query->commit();
});

it('rollback transaction manually', function (): void {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();
    $transaction = $this->getMockBuilder(SqlTransaction::class)->getMock();

    $transaction->expects($this->exactly(1))
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Transaction failed'));

    $connection->expects($this->once())
        ->method('beginTransaction')
        ->willReturn($transaction);

    $transaction->expects($this->once())
        ->method('rollback');

    $query = new QueryBuilder();
    $query->connection($connection);

    try {
        $query->beginTransaction();
        $query->from('users')->get();
    } catch (Throwable $th) {
        $query->rollBack();
    }
});

it('deletes records and returns deleted data', function () {
    $deletedData = [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($deletedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('status', 'inactive')
        ->deleteReturning(['id', 'name', 'email']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->toArray())->toBe($deletedData);
    expect($result->count())->toBe(2);
});

it('returns empty collection on delete returning error', function () {
    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Foreign key violation'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('id', 1)
        ->deleteReturning(['*']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->isEmpty())->toBeTrue();
});

it('deletes single record and returns its data', function () {
    $deletedData = [
        ['id' => 5, 'name' => 'Old User', 'email' => 'old@example.com', 'status' => 'deleted'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($deletedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('id', 5)
        ->deleteReturning(['id', 'name', 'email', 'status']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(1);
    expect($result->first())->toBe($deletedData[0]);
});

it('deletes records with returning all columns', function () {
    $deletedData = [
        ['id' => 1, 'name' => 'User 1', 'email' => 'user1@test.com', 'created_at' => '2024-01-01'],
        ['id' => 2, 'name' => 'User 2', 'email' => 'user2@test.com', 'created_at' => '2024-01-02'],
        ['id' => 3, 'name' => 'User 3', 'email' => 'user3@test.com', 'created_at' => '2024-01-03'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($deletedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereIn('id', [1, 2, 3])
        ->deleteReturning(['*']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(3);
    expect($result->toArray())->toBe($deletedData);
});

it('updates records and returns updated data', function () {
    $updatedData = [
        ['id' => 1, 'name' => 'John Updated', 'email' => 'john@new.com', 'status' => 'active'],
        ['id' => 2, 'name' => 'Jane Updated', 'email' => 'jane@new.com', 'status' => 'active'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($updatedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('status', 'pending')
        ->updateReturning(
            ['status' => 'active'],
            ['id', 'name', 'email', 'status']
        );

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->toArray())->toBe($updatedData);
    expect($result->count())->toBe(2);
});

it('returns empty collection on update returning error', function () {
    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Constraint violation'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('id', 1)
        ->updateReturning(['email' => 'duplicate@test.com'], ['*']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->isEmpty())->toBeTrue();
});

it('updates single record and returns its data', function () {
    $updatedData = [
        ['id' => 5, 'name' => 'Updated User', 'email' => 'updated@example.com', 'updated_at' => '2024-12-31'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($updatedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereEqual('id', 5)
        ->updateReturning(
            ['name' => 'Updated User', 'updated_at' => '2024-12-31'],
            ['id', 'name', 'email', 'updated_at']
        );

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(1);
    expect($result->first())->toBe($updatedData[0]);
});

it('updates records with returning all columns', function () {
    $updatedData = [
        ['id' => 1, 'name' => 'User 1', 'status' => 'active', 'updated_at' => '2024-12-31'],
        ['id' => 2, 'name' => 'User 2', 'status' => 'active', 'updated_at' => '2024-12-31'],
        ['id' => 3, 'name' => 'User 3', 'status' => 'active', 'updated_at' => '2024-12-31'],
    ];

    $connection = $this->getMockBuilder(PostgresqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturn(new Statement(new Result($updatedData)));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')
        ->whereIn('id', [1, 2, 3])
        ->updateReturning(['status' => 'active', 'updated_at' => '2024-12-31'], ['*']);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count())->toBe(3);
    expect($result->toArray())->toBe($updatedData);
});

it('inserts records using insert or ignore successfully', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->insertOrIgnore(['name' => 'Tony', 'email' => 'tony@example.com']);

    expect($result)->toBeTrue();
});

it('fails on insert or ignore records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Query error'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->insertOrIgnore(['name' => 'Tony', 'email' => 'tony@example.com']);

    expect($result)->toBeFalse();
});

it('inserts records from subquery successfully', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users_backup')->insertFrom(
        function ($subquery) {
            $subquery->from('users')->whereEqual('status', 'active');
        },
        ['id', 'name', 'email']
    );

    expect($result)->toBeTrue();
});

it('inserts records from subquery with ignore flag', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users_backup')->insertFrom(
        function ($subquery) {
            $subquery->from('users')->whereEqual('status', 'active');
        },
        ['id', 'name', 'email'],
        true
    );

    expect($result)->toBeTrue();
});

it('fails on insert from records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Insert from subquery failed'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users_backup')->insertFrom(
        function ($subquery) {
            $subquery->from('users')->whereEqual('status', 'active');
        },
        ['id', 'name', 'email']
    );

    expect($result)->toBeFalse();
});

it('upserts records successfully', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->upsert(
        ['name' => 'Tony', 'email' => 'tony@example.com', 'status' => 'active'],
        ['email']
    );

    expect($result)->toBeTrue();
});

it('upserts multiple records successfully', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->once())
        ->method('prepare')
        ->willReturnCallback(fn () => new Statement(new Result()));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->upsert(
        [
            ['name' => 'Tony', 'email' => 'tony@example.com'],
            ['name' => 'John', 'email' => 'john@example.com'],
        ],
        ['email']
    );

    expect($result)->toBeTrue();
});

it('fails on upsert records', function () {
    $connection = $this->getMockBuilder(MysqlConnectionPool::class)->getMock();

    $connection->expects($this->any())
        ->method('prepare')
        ->willThrowException(new SqlQueryError('Upsert failed'));

    $query = new QueryBuilder();
    $query->connection($connection);

    $result = $query->table('users')->upsert(
        ['name' => 'Tony', 'email' => 'tony@example.com'],
        ['email']
    );

    expect($result)->toBeFalse();
});
