<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Sql\SqlConnection;
use Amp\Sql\SqlTransaction;
use Closure;
use Phenix\Database\TransactionManager;
use Throwable;

trait HasTransaction
{
    protected SqlTransaction|null $transaction = null;

    public function transaction(Closure $callback): mixed
    {
        $this->transaction = $this->connection->beginTransaction();

        try {
            $scope = new TransactionManager($this);

            $result = $callback($scope);

            $this->transaction->commit();

            return $result;
        } catch (Throwable $e) {
            report($e);

            $this->transaction->rollBack();

            throw $e;
        }
    }

    public function beginTransaction(): TransactionManager
    {
        $this->transaction = $this->connection->beginTransaction();

        return new TransactionManager($this);
    }

    public function commit(): void
    {
        if ($this->transaction) {
            $this->transaction->commit();
            $this->transaction = null;
        }
    }

    public function rollBack(): void
    {
        if ($this->transaction) {
            $this->transaction->rollBack();
            $this->transaction = null;
        }
    }

    public function hasActiveTransaction(): bool
    {
        return isset($this->transaction) && $this->transaction !== null;
    }

    public function getTransaction(): SqlTransaction|null
    {
        return $this->transaction;
    }

    public function setTransaction(SqlTransaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    protected function exec(string $dml, array $params = []): mixed
    {
        return $this->getExecutor()->prepare($dml)->execute($params);
    }

    protected function getExecutor(): SqlTransaction|SqlConnection
    {
        return $this->hasActiveTransaction() ? $this->transaction : $this->connection;
    }
}
