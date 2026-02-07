<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\SqlResult;
use Closure;

class TransactionManager
{
    public function __construct(
        protected QueryBuilder $queryBuilder
    ) {
    }

    public function table(string $table): QueryBuilder
    {
        return $this->clone()->table($table);
    }

    public function from(Closure|string $table): QueryBuilder
    {
        return $this->clone()->from($table);
    }

    public function select(array $columns): QueryBuilder
    {
        return $this->clone()->select($columns);
    }

    public function selectAllColumns(): QueryBuilder
    {
        return $this->clone()->selectAllColumns();
    }

    public function unprepared(string $sql): SqlResult
    {
        return $this->clone()->unprepared($sql);
    }

    public function commit(): void
    {
        $this->queryBuilder->commit();
    }

    public function rollBack(): void
    {
        $this->queryBuilder->rollBack();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function clone(): QueryBuilder
    {
        return clone $this->queryBuilder;
    }
}
