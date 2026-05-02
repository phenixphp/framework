<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Contracts\RawValue;

function avg(string $column): Funct
{
    return Funct::avg($column);
}

function sum(string $column): Funct
{
    return Funct::sum($column);
}

function min_of(string $column): Funct
{
    return Funct::min($column);
}

function max_of(string $column): Funct
{
    return Funct::max($column);
}

function count_of(string $column): Funct
{
    return Funct::count($column);
}

function date_of(string $column): Funct
{
    return Funct::date($column);
}

function month(string $column): Funct
{
    return Funct::month($column);
}

function year(string $column): Funct
{
    return Funct::year($column);
}

/**
 * @param array<int, mixed> $columns
 */
function subquery(array $columns = ['*']): Subquery
{
    return Subquery::make()->select($columns);
}

function when_equal(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenEqual($column, $value, $result);
}

function when_not_equal(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenNotEqual($column, $value, $result);
}

function when_gt(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenGreaterThan($column, $value, $result);
}

function when_gte(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenGreaterThanOrEqual($column, $value, $result);
}

function when_lt(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenLessThan($column, $value, $result);
}

function when_lte(Funct|string $column, RawValue|string|int $value, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenLessThanOrEqual($column, $value, $result);
}

function when_null(string $column, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenNull($column, $result);
}

function when_not_null(string $column, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenNotNull($column, $result);
}

function when_true(string $column, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenTrue($column, $result);
}

function when_false(string $column, RawValue|string|int $result): SelectCase
{
    return Funct::case()->whenFalse($column, $result);
}
