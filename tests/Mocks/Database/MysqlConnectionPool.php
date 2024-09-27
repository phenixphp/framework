<?php

declare(strict_types=1);

namespace Tests\Mocks\Database;

use Amp\Mysql\MysqlConfig;
use Amp\Sql\Common\SqlCommonConnectionPool;
use Amp\Sql\SqlConfig;
use Amp\Sql\SqlConnector;
use Amp\Sql\SqlException;
use Amp\Sql\SqlResult;
use Amp\Sql\SqlStatement;
use Amp\Sql\SqlTransaction;
use Closure;
use Mockery;
use Tests\Mocks\Database\Result as FakeResult;
use Tests\Mocks\Database\Statement as FakeStatement;
use Throwable;

class MysqlConnectionPool extends SqlCommonConnectionPool
{
    protected FakeResult $fakeResult;
    protected Throwable|null $fakeError = null;

    public function __construct(SqlConfig|null $config = null, SqlConnector|null $connector = null)
    {
        parent::__construct(
            $config ?? MysqlConfig::fromString('host=host;user=user;password=password'),
            $connector ?? Mockery::mock(SqlConnector::class)
        );
    }

    public static function fake(array $result = []): self
    {
        $pool = new self();
        $pool->setFakeResult($result);

        return $pool;
    }

    public function setFakeResult(array $result): void
    {
        $this->fakeResult = new FakeResult($result);
    }

    public function throwDatabaseException(Throwable|null $error = null): self
    {
        $this->fakeError = $error ?? new SqlException('Fail trying database connection');

        return $this;
    }

    public function prepare(string $sql): SqlStatement
    {
        if (isset($this->fakeError)) {
            throw $this->fakeError;
        }

        return new FakeStatement($this->fakeResult);
    }

    protected function createStatement(SqlStatement $statement, Closure $release): SqlStatement
    {
        return $statement;
    }

    protected function createResult(SqlResult $result, Closure $release): SqlResult
    {
        return $result;
    }

    protected function createStatementPool(string $sql, Closure $prepare): SqlStatement
    {
        return new FakeStatement($this->fakeResult);

    }

    protected function createTransaction(SqlTransaction $transaction, Closure $release): SqlTransaction
    {
        return $transaction;
    }
}
