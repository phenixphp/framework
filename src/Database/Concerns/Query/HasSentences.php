<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Sql\SqlTransaction;
use Closure;
use League\Uri\Components\Query;
use League\Uri\Http;
use Phenix\Database\Constants\Action;
use Phenix\Database\Paginator;
use Throwable;

trait HasSentences
{
    protected SqlTransaction|null $transaction = null;

    public function paginate(Http $uri,  int $defaultPage = 1, int $defaultPerPage = 15): Paginator
    {
        $this->action = Action::SELECT;

        $query = Query::fromUri($uri);

        $currentPage = filter_var($query->get('page') ?? $defaultPage, FILTER_SANITIZE_NUMBER_INT);
        $currentPage = $currentPage === false ? $defaultPage : $currentPage;

        $perPage = filter_var($query->get('per_page') ?? $defaultPerPage, FILTER_SANITIZE_NUMBER_INT);
        $perPage = $perPage === false ? $defaultPerPage : $perPage;

        $countQuery = clone $this;

        $total = $countQuery->count();

        $data = $this->page((int) $currentPage, (int) $perPage)->get();

        return new Paginator($uri, $data, (int) $total, (int) $currentPage, (int) $perPage);
    }

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
