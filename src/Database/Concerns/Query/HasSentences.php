<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Amp\Mysql\Internal\MysqlPooledResult;
use Amp\Sql\SqlQueryError;
use Amp\Sql\SqlTransactionError;
use League\Uri\Components\Query;
use League\Uri\Http;
use Phenix\Database\Constants\Actions;
use Phenix\Database\Paginator;

trait HasSentences
{
    public function paginate(Http $uri,  int $defaultPage = 1, int $defaultPerPage = 15): Paginator
    {
        $this->action = Actions::SELECT;

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

    public function count(string $column = '*'): int
    {
        $this->action = Actions::SELECT;

        $this->countRows($column);

        [$dml, $params] = $this->toSql();

        /** @var array<string, int> $count */
        $count = $this->connection
            ->prepare($dml)
            ->execute($params)
            ->fetchRow();

        return array_values($count)[0];
    }

    public function insert(array $data): bool
    {
        [$dml, $params] = $this->insertRows($data)->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError $e) {
            report($e);

            return false;
        }
    }

    public function insertRow(array $data): int|string|bool
    {
        [$dml, $params] = $this->insertRows($data)->toSql();

        try {
            /** @var MysqlPooledResult $result */
            $result = $this->connection->prepare($dml)->execute($params);

            return $result->getLastInsertId();
        } catch (SqlQueryError|SqlTransactionError $e) {
            report($e);

            return false;
        }
    }

    public function exists(): bool
    {
        $this->action = Actions::EXISTS;

        $this->existsRows();

        [$dml, $params] = $this->toSql();

        $results = $this->connection->prepare($dml)->execute($params)->fetchRow();

        return (bool) array_values($results)[0];
    }

    public function doesntExist(): bool
    {
        return ! $this->exists();
    }

    public function update(array $values): bool
    {
        $this->updateRow($values);

        [$dml, $params] = $this->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError $e) {
            report($e);

            return false;
        }
    }

    public function delete(): bool
    {
        $this->deleteRows();

        [$dml, $params] = $this->toSql();

        try {
            $this->connection->prepare($dml)->execute($params);

            return true;
        } catch (SqlQueryError|SqlTransactionError $e) {
            report($e);

            return false;
        }
    }
}
