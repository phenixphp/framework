<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasDriver;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Concerns\Query\HasLock;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Contracts\Builder;
use Phenix\Database\Contracts\QueryBuilder;

abstract class QueryBase extends Clause implements QueryBuilder, Builder
{
    use HasDriver;
    use BuildsQuery;
    use HasLock;
    use HasJoinClause;

    protected string $table;

    protected Action $action;

    protected array $columns;

    protected array $values;

    protected array $joins;

    protected string $having;

    protected array $groupBy;

    protected array $orderBy;

    protected array $limit;

    protected array $offset;

    protected string $rawStatement;

    protected bool $ignore = false;

    protected array $uniqueColumns;

    public function __construct()
    {
        $this->ignore = false;

        $this->resetBaseProperties();
    }

    public function __clone(): void
    {
        $this->resetBaseProperties();
    }

    protected function resetBaseProperties(): void
    {
        $this->joins = [];
        $this->columns = [];
        $this->values = [];
        $this->clauses = [];
        $this->arguments = [];
        $this->uniqueColumns = [];
    }

    public function count(string $column = '*'): array|int
    {
        $this->action = Action::SELECT;

        $this->columns = [Functions::count($column)];

        return $this->toSql();
    }

    public function exists(): array|bool
    {
        $this->action = Action::EXISTS;

        $this->columns = [Operator::EXISTS->value];

        return $this->toSql();
    }

    public function doesntExist(): array|bool
    {
        $this->action = Action::EXISTS;

        $this->columns = [Operator::NOT_EXISTS->value];

        return $this->toSql();
    }

    public function insert(array $data): array|bool
    {
        $this->action = Action::INSERT;

        $this->prepareDataToInsert($data);

        return $this->toSql();
    }

    public function insertOrIgnore(array $values): array|bool
    {
        $this->ignore = true;

        $this->insert($values);

        return $this->toSql();
    }

    public function insertFrom(Closure $subquery, array $columns, bool $ignore = false): array|bool
    {
        $builder = new Subquery($this->driver);
        $builder->selectAllColumns();

        $subquery($builder);

        [$dml, $arguments] = $builder->toSql();

        $this->rawStatement = trim($dml, '()');

        $this->arguments = array_merge($this->arguments, $arguments);

        $this->action = Action::INSERT;

        $this->ignore = $ignore;

        $this->columns = $columns;

        return $this->toSql();
    }

    public function update(array $values): array|bool
    {
        $this->action = Action::UPDATE;

        $this->values = $values;

        return $this->toSql();
    }

    public function upsert(array $values, array $columns): array|bool
    {
        $this->action = Action::INSERT;

        $this->uniqueColumns = $columns;

        $this->prepareDataToInsert($values);

        return $this->toSql();
    }

    public function delete(): array|bool
    {
        $this->action = Action::DELETE;

        return $this->toSql();
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

        $this->columns = array_unique([...$this->columns, ...array_keys($data)]);

        $this->arguments = \array_merge($this->arguments, array_values($data));

        $this->values[] = array_fill(0, count($data), SQL::STD_PLACEHOLDER->value);
    }
}
