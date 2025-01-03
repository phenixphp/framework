<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\Operator;
use Phenix\Util\Arr;
use Stringable;

class SelectCase implements Stringable
{
    protected array $cases;
    protected Value|string $default;
    protected string $alias;

    public function __construct()
    {
        $this->cases = [];
    }

    public function whenEqual(Functions|string $column, Value|string|int $value, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenDistinct(Functions|string $column, Value|string|int $value, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::DISTINCT,
            $result,
            $value
        );

        return $this;
    }

    public function whenGreaterThan(Functions|string $column, Value|string|int $value, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::GREATER_THAN,
            $result,
            $value
        );

        return $this;
    }

    public function whenGreaterThanOrEqual(
        Functions|string $column,
        Value|string|int $value,
        Value|string $result
    ): self {
        $this->pushCase(
            $column,
            Operator::GREATER_THAN_OR_EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenLessThan(Functions|string $column, Value|string|int $value, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::LESS_THAN,
            $result,
            $value
        );

        return $this;
    }

    public function whenLessThanOrEqual(Functions|string $column, Value|string|int $value, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::LESS_THAN_OR_EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenNull(string $column, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_NULL,
            $result
        );

        return $this;
    }

    public function whenNotNull(string $column, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_NOT_NULL,
            $result
        );

        return $this;
    }

    public function whenTrue(string $column, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_TRUE,
            $result
        );

        return $this;
    }

    public function whenFalse(string $column, Value|string $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_FALSE,
            $result
        );

        return $this;
    }

    public function defaultResult(Value|string|int $value): self
    {
        $this->default = $value;

        return $this;
    }

    public function as(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function __toString(): string
    {
        $cases = array_map(function (array $case): array {
            return array_map(function (Operator|string $item): string {
                return match (true) {
                    $item instanceof Operator => $item->value,
                    default => (string) $item,
                };
            }, $case);
        }, $this->cases);

        if (isset($this->default)) {
            $cases[] = ['ELSE ' . strval($this->default)];
        }

        $cases[] = 'END';

        $dml = 'CASE ' . Arr::implodeDeeply($cases);

        if (isset($this->alias)) {
            $dml = '(' . $dml . ') AS ' . $this->alias;
        }

        return $dml;
    }

    protected function pushCase(
        Functions|string $column,
        Operator $operators,
        Value|string $result,
        Value|string|int|null $value = null
    ): void {
        $condition = array_filter([$column, $operators, $value]);

        $this->cases[] = ['WHEN', ...$condition, 'THEN', $result];
    }
}
