<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Constants\Actions;

class QueryGenerator extends QueryBase
{
    use BuildsQuery {
        insert as protected insertRows;
        insertOrIgnore as protected insertOrIgnoreRows;
        upsert as protected upsertRows;
        insertFrom as protected insertFromRows;
        update as protected updateRow;
        delete as protected deleteRows;
        count as protected countRows;
        exists as protected existsRows;
        doesntExist as protected doesntExistRows;
    }
    use HasJoinClause;

    public function insert(array $data): array
    {
        return $this->insertRows($data)->toSql();
    }

    public function insertOrIgnore(array $values): array
    {
        return $this->insertOrIgnoreRows($values)->toSql();
    }

    public function upsert(array $values, array $columns): array
    {
        return $this->upsertRows($values, $columns)->toSql();
    }

    public function insertFrom(Closure $subquery, array $columns, bool $ignore = false): array
    {
        return $this->insertFromRows($subquery, $columns, $ignore)->toSql();
    }

    public function update(array $values): array
    {
        return $this->updateRow($values)->toSql();
    }

    public function delete(): array
    {
        return $this->deleteRows()->toSql();
    }

    public function count(string $column = '*'): array
    {
        $this->action = Actions::SELECT;

        return $this->countRows($column)->toSql();
    }

    public function exists(): array
    {
        $this->action = Actions::SELECT;

        return $this->existsRows()->toSql();
    }

    public function doesntExist(): array
    {
        $this->action = Actions::SELECT;

        return $this->doesntExistRows()->toSql();
    }

    public function get(): array
    {
        $this->action = Actions::SELECT;

        return $this->toSql();
    }

    public function first(): array
    {
        $this->action = Actions::SELECT;

        return $this->limit(1)->toSql();
    }
}
