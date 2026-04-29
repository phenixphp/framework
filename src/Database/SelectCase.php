<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Concerns\HasDriver;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Contracts\RawValue;
use Phenix\Util\Arr;
use Stringable;

use function is_int;

class SelectCase implements Stringable
{
    use HasDriver;

    protected array $cases;

    protected RawValue|string|int $default;

    protected string $alias;

    public function __construct()
    {
        $this->cases = [];
    }

    public function whenEqual(Functions|string $column, RawValue|string|int $value, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenNotEqual(Functions|string $column, RawValue|string|int $value, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::NOT_EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenGreaterThan(Functions|string $column, RawValue|string|int $value, RawValue|string|int $result): self
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
        RawValue|string|int $value,
        RawValue|string|int $result
    ): self {
        $this->pushCase(
            $column,
            Operator::GREATER_THAN_OR_EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenLessThan(Functions|string $column, RawValue|string|int $value, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::LESS_THAN,
            $result,
            $value
        );

        return $this;
    }

    public function whenLessThanOrEqual(Functions|string $column, RawValue|string|int $value, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::LESS_THAN_OR_EQUAL,
            $result,
            $value
        );

        return $this;
    }

    public function whenNull(string $column, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_NULL,
            $result
        );

        return $this;
    }

    public function whenNotNull(string $column, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_NOT_NULL,
            $result
        );

        return $this;
    }

    public function whenTrue(string $column, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_TRUE,
            $result
        );

        return $this;
    }

    public function whenFalse(string $column, RawValue|string|int $result): self
    {
        $this->pushCase(
            $column,
            Operator::IS_FALSE,
            $result
        );

        return $this;
    }

    public function defaultResult(RawValue|string|int $value): self
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
        $cases = array_map($this->compileCase(...), $this->cases);

        if (isset($this->default)) {
            $cases[] = 'ELSE ' . $this->renderOperand($this->default);
        }

        $cases[] = 'END';

        $dml = 'CASE ' . Arr::implodeDeeply($cases, ' ');

        if (isset($this->alias)) {
            $alias = Wrapper::of($this->getDriver(), $this->alias);

            $dml = "({$dml}) AS {$alias}";
        }

        return $dml;
    }

    protected function pushCase(
        Functions|string $column,
        Operator $operators,
        RawValue|string|int $result,
        RawValue|string|int|null $value = null
    ): void {
        $condition = array_filter([$column, $operators, $value], static fn (mixed $item): bool => $item !== null);

        $this->cases[] = ['WHEN', ...$condition, 'THEN', $result];
    }

    protected function compileCase(array $case): string
    {
        $column = $this->compileColumn($case[1]);
        $operator = $case[2] instanceof Operator ? $case[2]->value : (string) $case[2];

        if (($case[3] ?? null) === 'THEN') {
            return "WHEN {$column} {$operator} THEN " . $this->renderOperand($case[4]);
        }

        return "WHEN {$column} {$operator} " . $this->renderOperand($case[3]) . " THEN " . $this->renderOperand($case[5]);
    }

    protected function compileColumn(Functions|string $column): string
    {
        if ($column instanceof Functions) {
            return (string) $column->setDriver($this->getDriver());
        }

        return Wrapper::column($this->getDriver(), $column);
    }

    protected function renderOperand(RawValue|string|int $value): string
    {
        if ($value instanceof RawValue || is_int($value)) {
            return (string) $value;
        }

        return (string) Value::from($value);
    }
}
