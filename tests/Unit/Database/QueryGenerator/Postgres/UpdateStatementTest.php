<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\QueryGenerator;

use function Pest\Faker\faker;

it('generates update statement', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;

    $sql = $query->table('users')
        ->whereEqual('id', 1)
        ->update(['name' => $name]);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET name = $1 WHERE id = $2";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$name, 1]);
});

it('generates update statement with many conditions and columns', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;

    $sql = $query->table('users')
        ->whereNotNull('verified_at')
        ->whereEqual('role_id', 2)
        ->update(['name' => $name, 'active' => true]);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET name = $1, active = $2 WHERE verified_at IS NOT NULL AND role_id = $3";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$name, true, 2]);
});

it('generates update statement with single column', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereEqual('id', 5)
        ->update(['status' => 'inactive']);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET status = $1 WHERE id = $2";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['inactive', 5]);
});

it('generates update statement with where in clause', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereIn('id', [1, 2, 3])
        ->update(['status' => 'active']);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET status = $1 WHERE id IN ($2, $3, $4)";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active', 1, 2, 3]);
});

it('generates update statement with multiple where clauses', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->whereEqual('status', 'pending')
        ->whereGreaterThan('created_at', '2024-01-01')
        ->update(['email' => $email, 'verified' => true]);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET email = $1, verified = $2 WHERE status = $3 AND created_at > $4";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, true, 'pending', '2024-01-01']);
});

it('generates update statement with where not equal', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereNotEqual('role', 'admin')
        ->update(['access_level' => 1]);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET access_level = $1 WHERE role != $2";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1, 'admin']);
});

it('generates update statement with where null', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->whereNull('deleted_at')
        ->update(['last_login' => '2024-12-30']);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET last_login = $1 WHERE deleted_at IS NULL";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['2024-12-30']);
});

it('generates update statement with multiple columns and complex where', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->whereEqual('status', 'active')
        ->whereNotNull('email_verified_at')
        ->whereLessThan('login_count', 5)
        ->update([
            'name' => $name,
            'email' => $email,
            'updated_at' => '2024-12-30',
        ]);

    [$dml, $params] = $sql;

    $expected = "UPDATE users SET name = $1, email = $2, updated_at = $3 "
        . "WHERE status = $4 AND email_verified_at IS NOT NULL AND login_count < $5";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$name, $email, '2024-12-30', 'active', 5]);
});
