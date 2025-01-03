<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Phenix\Database\Constants\Operator;
use Phenix\Database\QueryGenerator;

it('generates query to select a record by date', function (
    string $method,
    CarbonInterface|string $date,
    string $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE DATE(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['whereDateEqual', Carbon::now(), Carbon::now()->format('Y-m-d'), Operator::EQUAL->value],
    ['whereDateEqual', date('Y-m-d'), date('Y-m-d'), Operator::EQUAL->value],
    ['whereDateGreaterThan', date('Y-m-d'), date('Y-m-d'), Operator::GREATER_THAN->value],
    ['whereDateGreaterThanOrEqual', date('Y-m-d'), date('Y-m-d'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['whereDateLessThan', date('Y-m-d'), date('Y-m-d'), Operator::LESS_THAN->value],
    ['whereDateLessThanOrEqual', date('Y-m-d'), date('Y-m-d'), Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query to select a record by condition or by date', function (
    string $method,
    CarbonInterface|string $date,
    string $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereFalse('active')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE active IS FALSE OR DATE(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['orWhereDateEqual', date('Y-m-d'), date('Y-m-d'), Operator::EQUAL->value],
    ['orWhereDateGreaterThan', date('Y-m-d'), date('Y-m-d'), Operator::GREATER_THAN->value],
    ['orWhereDateGreaterThanOrEqual', date('Y-m-d'), date('Y-m-d'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['orWhereDateLessThan', date('Y-m-d'), date('Y-m-d'), Operator::LESS_THAN->value],
    ['orWhereDateLessThanOrEqual', date('Y-m-d'), date('Y-m-d'), Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query to select a record by month', function (
    string $method,
    CarbonInterface|int $date,
    int $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE MONTH(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['whereMonthEqual', Carbon::now(), Carbon::now()->format('m'), Operator::EQUAL->value],
    ['whereMonthEqual', date('m'), date('m'), Operator::EQUAL->value],
    ['whereMonthGreaterThan', date('m'), date('m'), Operator::GREATER_THAN->value],
    ['whereMonthGreaterThanOrEqual', date('m'), date('m'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['whereMonthLessThan', date('m'), date('m'), Operator::LESS_THAN->value],
    ['whereMonthLessThanOrEqual', date('m'), date('m'), Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query to select a record by condition or by month', function (
    string $method,
    CarbonInterface|int $date,
    int $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereFalse('active')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE active IS FALSE OR MONTH(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['orWhereMonthEqual', Carbon::now(), Carbon::now()->format('m'), Operator::EQUAL->value],
    ['orWhereMonthEqual', date('m'), date('m'), Operator::EQUAL->value],
    ['orWhereMonthGreaterThan', date('m'), date('m'), Operator::GREATER_THAN->value],
    ['orWhereMonthGreaterThanOrEqual', date('m'), date('m'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['orWhereMonthLessThan', date('m'), date('m'), Operator::LESS_THAN->value],
    ['orWhereMonthLessThanOrEqual', date('m'), date('m'), Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query to select a record by year', function (
    string $method,
    CarbonInterface|int $date,
    int $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE YEAR(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['whereYearEqual', Carbon::now(), Carbon::now()->format('Y'), Operator::EQUAL->value],
    ['whereYearEqual', date('Y'), date('Y'), Operator::EQUAL->value],
    ['whereYearGreaterThan', date('Y'), date('Y'), Operator::GREATER_THAN->value],
    ['whereYearGreaterThanOrEqual', date('Y'), date('Y'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['whereYearLessThan', date('Y'), date('Y'), Operator::LESS_THAN->value],
    ['whereYearLessThanOrEqual', date('Y'), date('Y'), Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query to select a record by condition or by year', function (
    string $method,
    CarbonInterface|int $date,
    int $value,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereFalse('active')
        ->{$method}('created_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE active IS FALSE OR YEAR(created_at) {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['orWhereYearEqual', Carbon::now(), Carbon::now()->format('Y'), Operator::EQUAL->value],
    ['orWhereYearEqual', date('Y'), date('Y'), Operator::EQUAL->value],
    ['orWhereYearGreaterThan', date('Y'), date('Y'), Operator::GREATER_THAN->value],
    ['orWhereYearGreaterThanOrEqual', date('Y'), date('Y'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['orWhereYearLessThan', date('Y'), date('Y'), Operator::LESS_THAN->value],
    ['orWhereYearLessThanOrEqual', date('Y'), date('Y'), Operator::LESS_THAN_OR_EQUAL->value],
]);
