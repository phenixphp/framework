<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Concerns\Query\HasDriver;
use Phenix\Database\Constants\Action;
use Phenix\Database\Contracts\Builder;
use Phenix\Database\Contracts\QueryBuilder;

abstract class QueryBase extends Clause implements QueryBuilder, Builder
{
    use HasDriver;

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

        $this->joins = [];
        $this->columns = [];
        $this->values = [];
        $this->clauses = [];
        $this->arguments = [];
        $this->uniqueColumns = [];
    }
}
