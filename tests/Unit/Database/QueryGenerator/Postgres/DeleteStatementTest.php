<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\QueryGenerator;

it('generates delete statement', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"id\" = $1";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
});

it('generates delete statement without clauses', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\"";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates delete statement with multiple where clauses', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereEqual('status', 'inactive')
        ->whereEqual('role', 'user')
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"status\" = $1 AND \"role\" = $2";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['inactive', 'user']);
});

it('generates delete statement with where in clause', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereIn('id', [1, 2, 3])
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"id\" IN ($1, $2, $3)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1, 2, 3]);
});

it('generates delete statement with where not equal', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereNotEqual('status', 'active')
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"status\" != $1";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active']);
});

it('generates delete statement with where greater than', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereGreaterThan('age', 18)
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"age\" > $1";

    expect($dml)->toBe($expected);
    expect($params)->toBe([18]);
});

it('generates delete statement with where less than', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereLessThan('age', 65)
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"age\" < $1";

    expect($dml)->toBe($expected);
    expect($params)->toBe([65]);
});

it('generates delete statement with where null', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereNull('deleted_at')
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"deleted_at\" IS NULL";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates delete statement with where not null', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereNotNull('email')
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"email\" IS NOT NULL";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates delete statement with returning clause', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->returning(['id', 'name', 'email'])
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"id\" = $1 RETURNING \"id\", \"name\", \"email\"";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
});

it('generates delete statement with returning all columns', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereIn('status', ['inactive', 'deleted'])
        ->returning(['*'])
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"status\" IN ($1, $2) RETURNING *";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['inactive', 'deleted']);
});

it('generates delete statement with returning without where clause', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->returning(['id', 'email'])
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" RETURNING \"id\", \"email\"";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates delete statement with multiple where clauses and returning', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereEqual('status', 'inactive')
        ->whereGreaterThan('created_at', '2024-01-01')
        ->returning(['id', 'name', 'status', 'created_at'])
        ->delete();

    [$dml, $params] = $sql;

    $expected = "DELETE FROM \"users\" WHERE \"status\" = $1 AND \"created_at\" > $2 RETURNING \"id\", \"name\", \"status\", \"created_at\"";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['inactive', '2024-01-01']);
});
