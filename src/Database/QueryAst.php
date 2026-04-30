<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Clauses\WhereClause;
use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Lock;

class QueryAst
{
    public Driver $driver;

    public Action $action;

    public string $table;

    /**
     * @var array<int, mixed>
     */
    public array $columns = ['*'];

    /**
     * Values for INSERT/UPDATE operations
     *
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * @var array<int, Join>
     */
    public array $joins = [];

    /**
     * @var array<int, WhereClause>
     */
    public array $wheres = [];

    public Having|null $having = null;

    /**
     * @var array<int, mixed>
     */
    public array $groups = [];

    /**
     * @var array<int, mixed>
     */
    public array $orders = [];

    public int|null $limit = null;

    public int|null $offset = null;

    public Lock|null $lock = null;

    /**
     * RETURNING clause columns (PostgreSQL, SQLite 3.35+)
     *
     * @var array<int, string>
     */
    public array $returning = [];

    /**
     * Prepared statement parameters
     *
     * @var array<int, mixed>
     */
    public array $params = [];

    /**
     * @var string|null
     */
    public string|null $rawStatement = null;

    /**
     * Whether to use INSERT IGNORE (MySQL)
     * */
    public bool $ignore = false;

    /**
     * Columns for UPSERT operations (ON DUPLICATE KEY / ON CONFLICT)
     *
     * @var array<int, string>
     */
    public array $uniqueColumns = [];
}
