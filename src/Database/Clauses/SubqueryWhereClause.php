<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;

/**
 * Subquery WHERE clause
 *
 * Handles: EXISTS, NOT EXISTS, comparisons with subqueries, IN/NOT IN with subquery
 * Examples:
 *   - WHERE EXISTS (SELECT ...)
 *   - WHERE id = (SELECT ...)
 *   - WHERE price > ANY (SELECT ...)
 *   - WHERE status IN (SELECT ...)
 */
class SubqueryWhereClause extends WhereClause
{
    protected Operator $comparisonOperator;

    protected string $sql;

    protected array $params;

    protected string|null $column;

    protected Operator|null $operator;

    public function __construct(
        Operator $comparisonOperator,
        string $sql,
        array $params,
        string|null $column = null,
        Operator|null $operator = null, // ANY, ALL, SOME
        LogicalConnector|null $connector = null
    ) {
        $this->comparisonOperator = $comparisonOperator;
        $this->sql = $sql;
        $this->params = $params;
        $this->column = $column;
        $this->operator = $operator;
        $this->connector = $connector;
    }

    public function getColumn(): string|null
    {
        return $this->column;
    }

    public function getOperator(): Operator
    {
        return $this->comparisonOperator;
    }

    public function getSubqueryOperator(): Operator|null
    {
        return $this->operator;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function renderValue(): string
    {
        // Render subquery with optional operator (ANY, ALL, SOME)
        return $this->operator?->value . $this->sql;
    }
}
