<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Concerns\Query\HasLock;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Contracts\Builder;
use Phenix\Database\Contracts\QueryBuilder;

use function count;

abstract class QueryBase extends Clause implements QueryBuilder, Builder
{
    use BuildsQuery;
    use HasLock;
    use HasJoinClause;

    protected QueryAst $ast;

    public function __construct()
    {
        $this->resetBaseProperties();
    }

    public function __clone(): void
    {
        $this->ast = clone $this->ast;
        $this->ast->lock = null;
    }

    protected function resetBaseProperties(): void
    {
        $this->ast = $this->makeFreshAst();
    }

    public function setDriver(Driver $driver): static
    {
        $this->driver = $driver;

        if (isset($this->ast)) {
            $this->ast->driver = $driver;
        }

        return $this;
    }

    protected function makeFreshAst(): QueryAst
    {
        $ast = new QueryAst();
        $ast->columns = [];

        if (isset($this->driver)) {
            $ast->driver = $this->driver;
        }

        return $ast;
    }

    /**
     * @return array<int, WhereClause>
     */
    protected function getClauses(): array
    {
        return $this->ast->wheres;
    }

    /**
     * @return array<int, mixed>
     */
    protected function getArguments(): array
    {
        return $this->ast->params;
    }

    protected function hasWhereClauses(): bool
    {
        return count($this->ast->wheres) > 0;
    }

    protected function addArguments(array $arguments): void
    {
        $this->ast->params = [...$this->ast->params, ...$arguments];
    }

    protected function pushWhereClause(
        WhereClause $where,
        LogicalConnector $logicalConnector = LogicalConnector::AND
    ): void {
        if ($this->hasWhereClauses()) {
            $where->setConnector($logicalConnector);
        }

        $this->ast->wheres[] = $where;
    }

    public function count(string $column = '*'): array|int
    {
        $this->ast->action = Action::SELECT;

        $this->ast->columns = [Funct::count($column)];

        return $this->toSql();
    }

    public function exists(): array|bool
    {
        $this->ast->action = Action::EXISTS;

        $this->ast->columns = [Operator::EXISTS->value];

        return $this->toSql();
    }

    public function doesntExist(): array|bool
    {
        $this->ast->action = Action::EXISTS;

        $this->ast->columns = [Operator::NOT_EXISTS->value];

        return $this->toSql();
    }

    public function insert(array $data): array|bool
    {
        $this->ast->action = Action::INSERT;

        $this->prepareDataToInsert($data);

        return $this->toSql();
    }

    public function insertOrIgnore(array $values): array|bool
    {
        $this->ast->ignore = true;

        $this->insert($values);

        return $this->toSql();
    }

    public function insertFrom(Closure $subquery, array $columns, bool $ignore = false): array|bool
    {
        $builder = new Subquery($this->driver);
        $builder->selectAllColumns();

        $subquery($builder);

        [$dml, $arguments] = $builder->toSql();

        $this->ast->rawStatement = trim($dml, '()');

        $this->addArguments($arguments);

        $this->ast->action = Action::INSERT;

        $this->ast->ignore = $ignore;

        $this->ast->columns = $columns;

        return $this->toSql();
    }

    public function update(array $values): array|bool
    {
        $this->ast->action = Action::UPDATE;

        $this->ast->values = $values;

        return $this->toSql();
    }

    public function upsert(array $values, array $columns): array|bool
    {
        $this->ast->action = Action::INSERT;

        $this->ast->uniqueColumns = $columns;

        $this->prepareDataToInsert($values);

        return $this->toSql();
    }

    public function delete(): array|bool
    {
        $this->ast->action = Action::DELETE;

        return $this->toSql();
    }

    /**
     * Specify columns to return after DELETE/UPDATE (PostgreSQL, SQLite 3.35+)
     *
     * @param array<int, string> $columns
     */
    public function returning(array $columns = ['*']): static
    {
        $this->ast->returning = array_unique($columns);

        return $this;
    }

    protected function prepareDataToInsert(array $data): void
    {
        if (array_is_list($data)) {
            foreach ($data as $record) {
                $this->prepareDataToInsert($record);
            }

            return;
        }

        ksort($data);

        $this->ast->columns = array_unique([...$this->ast->columns, ...array_keys($data)]);

        $this->addArguments(array_values($data));

        $this->ast->values[] = array_fill(0, count($data), SQL::PLACEHOLDER->value);
    }
}
