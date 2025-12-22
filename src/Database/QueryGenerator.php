<?php

declare(strict_types=1);

namespace Phenix\Database;

use Closure;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Driver;

class QueryGenerator extends QueryBase
{
    use BuildsQuery;
    use HasJoinClause;

    public function __construct(Driver $driver = Driver::MYSQL)
    {
        parent::__construct();

        $this->driver = $driver;
    }

    public function __clone(): void
    {
        parent::__clone();
        $this->isLocked = false;
        $this->lockType = null;
    }

    public function insert(array $data): array
    {
        return parent::insert($data);
    }

    public function insertOrIgnore(array $values): array
    {
        $this->ignore = true;

        $this->insert($values);

        return $this->toSql();
    }

    public function upsert(array $values, array $columns): array
    {
        return parent::upsert($values, $columns);
    }

    public function insertFrom(Closure $subquery, array $columns, bool $ignore = false): array
    {
        return parent::insertFrom($subquery, $columns, $ignore);
    }

    public function update(array $values): array
    {
        return parent::update($values);
    }

    public function delete(): array
    {
        return parent::delete();
    }

    public function count(string $column = '*'): array
    {
        return parent::count($column);
    }

    public function exists(): array
    {
        return parent::exists();
    }

    public function doesntExist(): array
    {
        return parent::doesntExist();
    }

    public function get(): array
    {
        $this->action = Action::SELECT;

        return $this->toSql();
    }

    public function first(): array
    {
        $this->action = Action::SELECT;

        return $this->limit(1)->toSql();
    }
}
