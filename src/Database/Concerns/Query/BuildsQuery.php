<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\Order;
use Phenix\Database\Dialects\DialectFactory;
use Phenix\Database\Functions;
use Phenix\Database\Having;
use Phenix\Database\QueryAst;
use Phenix\Database\SelectCase;
use Phenix\Database\Subquery;

use function is_string;

trait BuildsQuery
{
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function from(Closure|string $table): static
    {
        if ($table instanceof Closure) {
            $builder = new Subquery($this->driver);
            $builder->setDriver($this->driver);
            $builder->selectAllColumns();

            $table($builder);

            [$dml, $arguments] = $builder->toSql();

            $this->table($dml);

            $this->arguments = array_merge($this->arguments, $arguments);

        } else {
            $this->table($table);
        }

        return $this;
    }

    public function select(array $columns): static
    {
        $this->action = Action::SELECT;

        $this->columns = $columns;

        return $this;
    }

    public function selectAllColumns(): static
    {
        $this->select(['*']);

        return $this;
    }

    public function groupBy(Functions|array|string $column): static
    {
        if ($column instanceof Functions || is_string($column)) {
            $column = [$column];
        }

        $this->groupBy = $column;

        return $this;
    }

    public function having(Closure $clause): static
    {
        $having = new Having();
        $having->setDriver($this->driver);

        $clause($having);

        $this->having = $having;

        return $this;
    }

    public function orderBy(SelectCase|array|string $column, Order $order = Order::DESC): static
    {
        if ($column instanceof SelectCase || is_string($column)) {
            $column = [$column];
        }

        $this->orderBy = [$column, $order->value];

        return $this;
    }

    public function limit(int $number): static
    {
        $this->limit = [Operator::LIMIT->value, abs($number)];

        return $this;
    }

    public function page(int $page = 1, int $perPage = 15): static
    {
        $this->limit($perPage);

        $page = $page < 1 ? 1 : $page;

        $offset = $page === 1 ? 0 : (($page - 1) * abs($perPage));

        $this->offset = [Operator::OFFSET->value, $offset];

        return $this;
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function toSql(): array
    {
        $ast = $this->buildAst();
        $dialect = DialectFactory::fromDriver($this->driver);

        return $dialect->compile($ast);
    }

    protected function buildAst(): QueryAst
    {
        $ast = new QueryAst();
        $ast->driver = $this->driver;
        $ast->action = $this->action;
        $ast->table = $this->table;
        $ast->columns = $this->columns;
        $ast->values = $this->values ?? [];
        $ast->wheres = $this->clauses ?? [];
        $ast->joins = $this->joins ?? [];
        $ast->groups = $this->groupBy ?? [];
        $ast->orders = $this->orderBy ?? [];
        $ast->limit = isset($this->limit) ? $this->limit[1] : null;
        $ast->offset = isset($this->offset) ? $this->offset[1] : null;
        $ast->lock = $this->lockType ?? null;
        $ast->having = $this->having ?? null;
        $ast->rawStatement = $this->rawStatement ?? null;
        $ast->ignore = $this->ignore ?? false;
        $ast->uniqueColumns = $this->uniqueColumns ?? [];
        $ast->returning = $this->returning ?? [];
        $ast->params = $this->arguments;

        return $ast;
    }
}
