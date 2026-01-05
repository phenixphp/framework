<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\QueryGenerator;
use Phenix\Database\Subquery;

use function Pest\Faker\faker;

it('generates insert into statement', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->insert([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES ($1, $2)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates insert into statement with data collection', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->insert([
            [
                'name' => $name,
                'email' => $email,
            ],
            [
                'name' => $name,
                'email' => $email,
            ],
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES ($1, $2), ($3, $4)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name, $email, $name]);
});

it('generates insert ignore into statement', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->insertOrIgnore([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES ($1, $2) ON CONFLICT DO NOTHING";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates upsert statement to handle duplicate keys', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->upsert([
            'name' => $name,
            'email' => $email,
        ], ['name']);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES ($1, $2) "
        . "ON CONFLICT (name) DO UPDATE SET name = EXCLUDED.name";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates upsert statement to handle duplicate keys with many unique columns', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $data = [
        'name' => faker()->name,
        'username' => faker()->userName,
        'email' => faker()->freeEmail,
    ];

    $sql = $query->table('users')
        ->upsert($data, ['name', 'username']);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name, username) VALUES ($1, $2, $3) "
        . "ON CONFLICT (name, username) DO UPDATE SET name = EXCLUDED.name, username = EXCLUDED.username";

    \ksort($data);

    expect($dml)->toBe($expected);
    expect($params)->toBe(\array_values($data));
});

it('generates insert statement from subquery', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->insertFrom(function (Subquery $subquery) {
            $subquery->table('customers')
                ->select(['name', 'email'])
                ->whereNotNull('verified_at');
        }, ['name', 'email']);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (name, email) SELECT name, email FROM customers WHERE verified_at IS NOT NULL";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates insert ignore statement from subquery', function () {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->table('users')
        ->insertFrom(function (Subquery $subquery) {
            $subquery->table('customers')
                ->select(['name', 'email'])
                ->whereNotNull('verified_at');
        }, ['name', 'email'], true);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (name, email) "
        . "SELECT name, email FROM customers WHERE verified_at IS NOT NULL "
        . "ON CONFLICT DO NOTHING";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});
