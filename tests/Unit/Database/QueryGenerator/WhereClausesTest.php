<?php

declare(strict_types=1);

use Phenix\Database\Constants\Operator;
use Phenix\Database\Constants\Order;
use Phenix\Database\Functions;
use Phenix\Database\QueryGenerator;
use Phenix\Database\Subquery;

it('generates query to select a record by column', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users WHERE id = ?');
    expect($params)->toBe([1]);
});

it('generates query to select a record using many clause', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereEqual('username', 'john')
        ->whereEqual('email', 'john@mail.com')
        ->whereEqual('document', 123456)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users WHERE username = ? AND email = ? AND document = ?');
    expect($params)->toBe(['john', 'john@mail.com', 123456]);
});

it('generates query to select using comparison clause', function (
    string $method,
    string $column,
    string $operator,
    string|int $value
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}($column, $value)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE {$column} {$operator} ?");
    expect($params)->toBe([$value]);
})->with([
    ['whereDistinct', 'id', Operator::DISTINCT->value, 1],
    ['whereGreaterThan', 'id', Operator::GREATER_THAN->value, 1],
    ['whereGreaterThanOrEqual', 'id', Operator::GREATER_THAN_OR_EQUAL->value, 1],
    ['whereLessThan', 'id', Operator::LESS_THAN->value, 1],
    ['whereLessThanOrEqual', 'id', Operator::LESS_THAN_OR_EQUAL->value, 1],
]);

it('generates query selecting specific columns', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->select(['id', 'name', 'email'])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT id, name, email FROM users WHERE id = ?');
    expect($params)->toBe([1]);
});


it('generates query using in and not in operators', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('id', [1, 2, 3])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE id {$operator} (?, ?, ?)");
    expect($params)->toBe([1, 2, 3]);
})->with([
    ['whereIn', Operator::IN->value],
    ['whereNotIn', Operator::NOT_IN->value],
]);

it('generates query using in and not in operators with subquery', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('id', function (Subquery $query) {
            $query->select(['id'])
                ->from('users')
                ->whereGreaterThanOrEqual('created_at', date('Y-m-d'));
        })
        ->get();

    [$dml, $params] = $sql;

    $date = date('Y-m-d');

    $expected = "SELECT * FROM users WHERE id {$operator} "
        . "(SELECT id FROM users WHERE created_at >= ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$date]);
})->with([
    ['whereIn', Operator::IN->value],
    ['whereNotIn', Operator::NOT_IN->value],
]);

it('generates query to select null or not null columns', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('verified_at')
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE verified_at {$operator}");
    expect($params)->toBe([]);
})->with([
    ['whereNull', Operator::IS_NULL->value],
    ['whereNotNull', Operator::IS_NOT_NULL->value],
]);

it('generates query to select by column or null or not null columns', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $date = date('Y-m-d');

    $sql = $query->table('users')
        ->whereGreaterThan('created_at', $date)
        ->{$method}('verified_at')
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE created_at > ? OR verified_at {$operator}");
    expect($params)->toBe([$date]);
})->with([
    ['orWhereNull', Operator::IS_NULL->value],
    ['orWhereNotNull', Operator::IS_NOT_NULL->value],
]);

it('generates query to select boolean columns', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('enabled')
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE enabled {$operator}");
    expect($params)->toBe([]);
})->with([
    ['whereTrue', Operator::IS_TRUE->value],
    ['whereFalse', Operator::IS_FALSE->value],
]);

it('generates query to select by column or boolean column', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $date = date('Y-m-d');

    $sql = $query->table('users')
        ->whereGreaterThan('created_at', $date)
        ->{$method}('enabled')
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE created_at > ? OR enabled {$operator}");
    expect($params)->toBe([$date]);
})->with([
    ['orWhereTrue', Operator::IS_TRUE->value],
    ['orWhereFalse', Operator::IS_FALSE->value],
]);

it('generates query using logical connectors', function () {
    $query = new QueryGenerator();

    $date = date('Y-m-d');

    $sql = $query->table('users')
        ->whereNotNull('verified_at')
        ->whereGreaterThan('created_at', $date)
        ->orWhereLessThan('updated_at', $date)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE verified_at IS NOT NULL AND created_at > ? OR updated_at < ?");
    expect($params)->toBe([$date, $date]);
});

it('generates query using the or operator between the and operators', function () {
    $query = new QueryGenerator();

    $date = date('Y-m-d');

    $sql = $query->table('users')
        ->whereGreaterThan('created_at', $date)
        ->orWhereLessThan('updated_at', $date)
        ->whereNotNull('verified_at')
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE created_at > ? OR updated_at < ? AND verified_at IS NOT NULL");
    expect($params)->toBe([$date, $date]);
});

it('generates queries using logical connectors', function (
    string $method,
    string $column,
    array|string $value,
    string $operator
) {
    $placeholders = '?';

    if (\is_array($value)) {
        $params = array_pad([], count($value), '?');

        $placeholders = '(' . implode(', ', $params) . ')';
    }

    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereNotNull('verified_at')
        ->{$method}($column, $value)
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE verified_at IS NOT NULL OR {$column} {$operator} {$placeholders}");
    expect($params)->toBe([...(array)$value]);
})->with([
    ['orWhereLessThan', 'updated_at', date('Y-m-d'), Operator::LESS_THAN->value],
    ['orWhereEqual', 'updated_at', date('Y-m-d'), Operator::EQUAL->value],
    ['orWhereDistinct', 'updated_at', date('Y-m-d'), Operator::DISTINCT->value],
    ['orWhereGreaterThan', 'updated_at', date('Y-m-d'), Operator::GREATER_THAN->value],
    ['orWhereGreaterThanOrEqual', 'updated_at', date('Y-m-d'), Operator::GREATER_THAN_OR_EQUAL->value],
    ['orWhereLessThan', 'updated_at', date('Y-m-d'), Operator::LESS_THAN->value],
    ['orWhereLessThanOrEqual', 'updated_at', date('Y-m-d'), Operator::LESS_THAN_OR_EQUAL->value],
    ['orWhereIn', 'status', ['enabled', 'verified'], Operator::IN->value],
    ['orWhereNotIn', 'status', ['disabled', 'banned'], Operator::NOT_IN->value],
]);

it('generates query to select between columns', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}('age', [20, 30])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE age {$operator} ? AND ?");
    expect($params)->toBe([20, 30]);
})->with([
    ['whereBetween', Operator::BETWEEN->value],
    ['whereNotBetween', Operator::NOT_BETWEEN->value],
]);

it('generates query to select by column or between columns', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $date = date('Y-m-d');
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');

    $sql = $query->table('users')
        ->whereGreaterThan('created_at', $date)
        ->{$method}('updated_at', [$startDate, $endDate])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users WHERE created_at > ? OR updated_at {$operator} ? AND ?");
    expect($params)->toBe([$date, $startDate, $endDate]);
})->with([
    ['orWhereBetween', Operator::BETWEEN->value],
    ['orWhereNotBetween', Operator::NOT_BETWEEN->value],
]);

it('generates a column-ordered query', function (array|string $column, string $order) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->orderBy($column, Order::from($order))
        ->get();

    [$dml, $params] = $sql;

    $operator = Operator::ORDER_BY->value;

    $column = implode(', ', (array) $column);

    expect($dml)->toBe("SELECT * FROM users {$operator} {$column} {$order}");
    expect($params)->toBe($params);
})->with([
    ['id', Order::ASC->value],
    [['id', 'created_at'], Order::ASC->value],
    ['id', Order::DESC->value],
    [['id', 'created_at'], Order::DESC->value],
]);

it('generates a column-ordered query using select-case', function () {
    $case = Functions::case()
        ->whenNull('city', 'country')
        ->defaultResult('city');

    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->orderBy($case, Order::ASC)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT * FROM users ORDER BY (CASE WHEN city IS NULL THEN country ELSE city END) ASC");
    expect($params)->toBe($params);
});

it('generates a limited query', function (array|string $column, string $order) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->orderBy($column, Order::from($order))
        ->limit(1)
        ->get();

    [$dml, $params] = $sql;

    $operator = Operator::ORDER_BY->value;

    $column = implode(', ', (array) $column);

    expect($dml)->toBe("SELECT * FROM users WHERE id = ? {$operator} {$column} {$order} LIMIT 1");
    expect($params)->toBe([1]);
})->with([
    ['id', Order::ASC->value],
    [['id', 'created_at'], Order::ASC->value],
    ['id', Order::DESC->value],
    [['id', 'created_at'], Order::DESC->value],
]);

it('generates a query with a exists subquery in where clause', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->{$method}(function (Subquery $query) {
            $query->table('user_role')
                ->whereEqual('user_id', 1)
                ->whereEqual('role_id', 9)
                ->limit(1);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT * FROM users WHERE {$operator} "
        . "(SELECT * FROM user_role WHERE user_id = ? AND role_id = ? LIMIT 1)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1, 9]);
})->with([
    ['whereExists', Operator::EXISTS->value],
    ['whereNotExists', Operator::NOT_EXISTS->value],
]);

it('generates a query to select by column or when exists or not exists subquery', function (
    string $method,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->whereTrue('is_admin')
        ->{$method}(function (Subquery $query) {
            $query->table('user_role')
                ->whereEqual('user_id', 1)
                ->limit(1);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT * FROM users WHERE is_admin IS TRUE OR {$operator} "
        . "(SELECT * FROM user_role WHERE user_id = ? LIMIT 1)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
})->with([
    ['orWhereExists', Operator::EXISTS->value],
    ['orWhereNotExists', Operator::NOT_EXISTS->value],
]);

it('generates query to select using comparison clause with subqueries and functions', function (
    string $method,
    string $column,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('products')
        ->{$method}($column, function (Subquery $subquery) {
            $subquery->select([Functions::max('price')])->from('products');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT * FROM products WHERE {$column} {$operator} "
        . '(SELECT ' . Functions::max('price') . ' FROM products)';

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['whereEqual', 'price', Operator::EQUAL->value],
    ['whereDistinct', 'price', Operator::DISTINCT->value],
    ['whereGreaterThan', 'price', Operator::GREATER_THAN->value],
    ['whereGreaterThanOrEqual', 'price', Operator::GREATER_THAN_OR_EQUAL->value],
    ['whereLessThan', 'price', Operator::LESS_THAN->value],
    ['whereLessThanOrEqual', 'price', Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query using comparison clause with subqueries and any, all, some operators', function (
    string $method,
    string $comparisonOperator,
    string $operator
) {
    $query = new QueryGenerator();

    $sql = $query->table('products')
        ->{$method}('id', function (Subquery $subquery) {
            $subquery->select(['product_id'])
                ->from('orders')
                ->whereGreaterThan('quantity', 10);
        })
        ->select(['description'])
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT description FROM products WHERE id {$comparisonOperator} {$operator}"
        . "(SELECT product_id FROM orders WHERE quantity > ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([10]);
})->with([
    ['whereAnyEqual', Operator::EQUAL->value, Operator::ANY->value],
    ['whereAnyDistinct', Operator::DISTINCT->value, Operator::ANY->value],
    ['whereAnyGreaterThan', Operator::GREATER_THAN->value, Operator::ANY->value],
    ['whereAnyGreaterThanOrEqual', Operator::GREATER_THAN_OR_EQUAL->value, Operator::ANY->value],
    ['whereAnyLessThan', Operator::LESS_THAN->value, Operator::ANY->value],
    ['whereAnyLessThanOrEqual', Operator::LESS_THAN_OR_EQUAL->value, Operator::ANY->value],

    ['whereAllEqual', Operator::EQUAL->value, Operator::ALL->value],
    ['whereAllDistinct', Operator::DISTINCT->value, Operator::ALL->value],
    ['whereAllGreaterThan', Operator::GREATER_THAN->value, Operator::ALL->value],
    ['whereAllGreaterThanOrEqual', Operator::GREATER_THAN_OR_EQUAL->value, Operator::ALL->value],
    ['whereAllLessThan', Operator::LESS_THAN->value, Operator::ALL->value],
    ['whereAllLessThanOrEqual', Operator::LESS_THAN_OR_EQUAL->value, Operator::ALL->value],

    ['whereSomeEqual', Operator::EQUAL->value, Operator::SOME->value],
    ['whereSomeDistinct', Operator::DISTINCT->value, Operator::SOME->value],
    ['whereSomeGreaterThan', Operator::GREATER_THAN->value, Operator::SOME->value],
    ['whereSomeGreaterThanOrEqual', Operator::GREATER_THAN_OR_EQUAL->value, Operator::SOME->value],
    ['whereSomeLessThan', Operator::LESS_THAN->value, Operator::SOME->value],
    ['whereSomeLessThanOrEqual', Operator::LESS_THAN_OR_EQUAL->value, Operator::SOME->value],
]);

it('generates query with row subquery', function (string $method, string $operator) {
    $query = new QueryGenerator();

    $sql = $query->table('employees')
        ->{$method}(['manager_id', 'department_id'], function (Subquery $subquery) {
            $subquery->select(['id, department_id'])
                ->from('managers')
                ->whereEqual('location_id', 1);
        })
        ->select(['name'])
        ->get();

    [$dml, $params] = $sql;

    $subquery = 'SELECT id, department_id FROM managers WHERE location_id = ?';

    $expected = "SELECT name FROM employees "
        . "WHERE ROW(manager_id, department_id) {$operator} ({$subquery})";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
})->with([
    ['whereRowEqual', Operator::EQUAL->value],
    ['whereRowDistinct', Operator::DISTINCT->value],
    ['whereRowGreaterThan', Operator::GREATER_THAN->value],
    ['whereRowGreaterThanOrEqual', Operator::GREATER_THAN_OR_EQUAL->value],
    ['whereRowLessThan', Operator::LESS_THAN->value],
    ['whereRowLessThanOrEqual', Operator::LESS_THAN_OR_EQUAL->value],
    ['whereRowIn', Operator::IN->value],
    ['whereRowNotIn', Operator::NOT_IN->value],
]);

it('clone query generator successfully', function () {
    $query = new QueryGenerator();

    $queryBuilder = $query->table('users')
        ->whereEqual('id', 1)
        ->lockForUpdate();

    $cloned = clone $queryBuilder;

    expect($cloned)->toBeInstanceOf(QueryGenerator::class);
    expect($cloned->isLocked())->toBeFalse();
});
