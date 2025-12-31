<?php

declare(strict_types=1);

namespace Phenix\Database\Clauses;

use Phenix\Database\Constants\LogicalConnector;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\SQL;

use function count;
use function is_array;

class BasicWhereClause extends WhereClause
{
    protected string $column;

    protected Operator $operator;

    protected array|string|int $value;

    protected bool $usePlaceholder;

    public function __construct(
        string $column,
        Operator $operator,
        array|string|int $value,
        LogicalConnector|null $connector = null,
        bool $usePlaceholder = false
    ) {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->connector = $connector;
        $this->usePlaceholder = $usePlaceholder;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOperator(): Operator
    {
        return $this->operator;
    }

    public function getValue(): array|string|int
    {
        return $this->value;
    }

    public function renderValue(): string
    {
        if ($this->usePlaceholder) {
            // In WHERE context with parameterized queries, use placeholder
            if (is_array($this->value)) {
                return '(' . implode(', ', array_fill(0, count($this->value), SQL::PLACEHOLDER->value)) . ')';
            }

            return SQL::PLACEHOLDER->value;
        }

        // In JOIN ON context, render the value directly (typically a column name)
        return (string) $this->value;
    }

    public function getValueCount(): int
    {
        if (is_array($this->value)) {
            return count($this->value);
        }

        return 1;
    }

    public function isInOperator(): bool
    {
        return $this->operator === Operator::IN || $this->operator === Operator::NOT_IN;
    }
}
