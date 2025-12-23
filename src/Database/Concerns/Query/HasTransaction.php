<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Sql\SqlTransaction;
use Closure;
use Throwable;

trait HasTransaction
{
    protected SqlTransaction|null $transaction = null;

    public function transaction(Closure $callback): mixed
    {
        /** @var SqlTransaction $transaction */
        $transaction = $this->connection->beginTransaction();

        $this->transaction = $transaction;

        try {
            $result = $callback($this);

            $transaction->commit();

            unset($this->transaction);

            return $result;
        } catch (Throwable $e) {
            report($e);

            $transaction->rollBack();

            unset($this->transaction);

            throw $e;
        }
    }

    public function beginTransaction(): SqlTransaction
    {
        $this->transaction = $this->connection->beginTransaction();

        return $this->transaction;
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

    protected function exec(string $dml, array $params = []): mixed
    {
        $executor = $this->hasActiveTransaction() ? $this->transaction : $this->connection;

        return $executor->prepare($dml)->execute($params);
    }
}
