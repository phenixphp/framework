<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Contracts\Database\Builder;
use Phenix\Contracts\Database\QueryBuilder;
use Phenix\Database\Constants\Actions;

abstract class QueryBase extends Clause implements QueryBuilder, Builder
{
    protected string $table;
    protected Actions $action;
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
