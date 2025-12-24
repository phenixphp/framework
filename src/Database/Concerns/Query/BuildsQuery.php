<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns\Query;

use Closure;
use Phenix\Util\Arr;
use Phenix\Database\Value;
use Phenix\Database\Having;
use Phenix\Database\QueryAst;
use Phenix\Database\Subquery;
use Phenix\Database\Functions;
use Phenix\Database\SelectCase;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Constants\Order;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Dialects\DialectFactory;

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
        $column = match (true) {
            $column instanceof Functions => (string) $column,
            default => $column,
        };

        $this->groupBy = [Operator::GROUP_BY->value, Arr::implodeDeeply((array) $column, ', ')];

        return $this;
    }

    public function having(Closure $clause): static
    {
        $having = new Having();

        $clause($having);

        [$dml, $arguments] = $having->toSql();

        $this->having = $dml;

        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    public function orderBy(SelectCase|array|string $column, Order $order = Order::DESC): static
    {
        $column = match (true) {
            $column instanceof SelectCase => '(' . $column . ')',
            default => $column,
        };

        $this->orderBy = [Operator::ORDER_BY->value, Arr::implodeDeeply((array) $column, ', '), $order->value];

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
        $ast->params = $this->arguments;

        return $ast;
    }

    protected function buildSelectQuery(): string
    {
        $this->columns = empty($this->columns) ? ['*'] : $this->columns;

        $query = [
            'SELECT',
            $this->prepareColumns($this->columns),
            'FROM',
            $this->table,
            $this->joins,
        ];

        if (! empty($this->clauses)) {
            $query[] = 'WHERE';
            $query[] = $this->prepareClauses($this->clauses);
        }

        if (isset($this->having)) {
            $query[] = $this->having;
        }

        if (isset($this->groupBy)) {
            $query[] = Arr::implodeDeeply($this->groupBy);
        }

        if (isset($this->orderBy)) {
            $query[] = Arr::implodeDeeply($this->orderBy);
        }

        if (isset($this->limit)) {
            $query[] = Arr::implodeDeeply($this->limit);
        }

        if (isset($this->offset)) {
            $query[] = Arr::implodeDeeply($this->offset);

        }

        if (isset($this->lockType)) {
            $query[] = $this->buildLock();
        }

        return Arr::implodeDeeply($query);
    }

    protected function buildExistsQuery(): string
    {
        $query = ['SELECT'];
        $query[] = $this->columns[0];

        $subquery[] = "SELECT 1 FROM {$this->table}";

        if (! empty($this->clauses)) {
            $subquery[] = 'WHERE';
            $subquery[] = $this->prepareClauses($this->clauses);
        }

        $query[] = '(' . Arr::implodeDeeply($subquery) . ') AS ' . Value::from('exists');

        return Arr::implodeDeeply($query);
    }

    private function buildInsertSentence(): string
    {
        $dml = [
            $this->ignore ? 'INSERT IGNORE INTO' : 'INSERT INTO',
            $this->table,
            '(' . Arr::implodeDeeply($this->columns, ', ') . ')',
        ];

        if (isset($this->rawStatement)) {
            $dml[] = $this->rawStatement;
        } else {
            $dml[] = 'VALUES';

            $placeholders = array_map(function (array $value): string {
                return '(' . Arr::implodeDeeply($value, ', ') . ')';
            }, $this->values);

            $dml[] = Arr::implodeDeeply($placeholders, ', ');

            if (! empty($this->uniqueColumns)) {
                $dml[] = 'ON DUPLICATE KEY UPDATE';

                $columns = array_map(function (string $column): string {
                    return "{$column} = VALUES({$column})";
                }, $this->uniqueColumns);

                $dml[] = Arr::implodeDeeply($columns, ', ');
            }
        }

        return Arr::implodeDeeply($dml);
    }

    private function buildUpdateSentence(): string
    {
        $dml = [
            'UPDATE',
            $this->table,
            'SET',
        ];

        $columns = [];
        $arguments = [];

        foreach ($this->values as $column => $value) {
            $arguments[] = $value;

            $columns[] = "{$column} = " . SQL::PLACEHOLDER->value;
        }

        $this->arguments = [...$arguments, ...$this->arguments];

        $dml[] = Arr::implodeDeeply($columns, ', ');

        if (! empty($this->clauses)) {
            $dml[] = 'WHERE';
            $dml[] = $this->prepareClauses($this->clauses);
        }

        return Arr::implodeDeeply($dml);
    }

    private function buildDeleteSentence(): string
    {
        $dml = [
            'DELETE FROM',
            $this->table,
        ];

        if (! empty($this->clauses)) {
            $dml[] = 'WHERE';
            $dml[] = $this->prepareClauses($this->clauses);
        }

        return Arr::implodeDeeply($dml);
    }
}
