<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Database\Constants\Action;
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
        $this->ast->table = $table;

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

            $this->addArguments($arguments);

        } else {
            $this->table($table);
        }

        return $this;
    }

    public function select(array $columns): static
    {
        $this->ast->action = Action::SELECT;

        $this->ast->columns = $columns;

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

        $this->ast->groups = $column;

        return $this;
    }

    public function having(Closure $clause): static
    {
        $having = new Having();
        $having->setDriver($this->driver);

        $clause($having);

        $this->ast->having = $having;

        return $this;
    }

    public function orderBy(SelectCase|array|string $column, Order $order = Order::DESC): static
    {
        if ($column instanceof SelectCase || is_string($column)) {
            $column = [$column];
        }

        $this->ast->orders = [$column, $order->value];

        return $this;
    }

    public function limit(int $number): static
    {
        $this->ast->limit = abs($number);

        return $this;
    }

    public function page(int $page = 1, int $perPage = 15): static
    {
        $this->limit($perPage);

        $page = $page < 1 ? 1 : $page;

        $offset = $page === 1 ? 0 : (($page - 1) * abs($perPage));

        $this->ast->offset = $offset;

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
        return $this->ast;
    }
}
