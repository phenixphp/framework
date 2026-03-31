<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\QueryGenerator;
use Phenix\Database\Subquery;

use function Pest\Faker\faker;

it('generates insert into statement', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->insert([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES (?, ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates insert into statement with data collection', function () {
    $query = new QueryGenerator(Driver::SQLITE);

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

    $expected = "INSERT INTO users (email, name) VALUES (?, ?), (?, ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name, $email, $name]);
});

it('generates insert ignore into statement', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->insertOrIgnore([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT OR IGNORE INTO users (email, name) VALUES (?, ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates insert into statement with returning clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->returning(['id'])
        ->insert([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES (?, ?) RETURNING id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates insert ignore into statement with returning clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->returning(['id', 'email'])
        ->insertOrIgnore([
            'name' => $name,
            'email' => $email,
        ]);

    [$dml, $params] = $sql;

    $expected = "INSERT OR IGNORE INTO users (email, name) VALUES (?, ?) RETURNING id, email";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates upsert statement to handle duplicate keys', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $name = faker()->name;
    $email = faker()->freeEmail;

    $sql = $query->table('users')
        ->upsert([
            'name' => $name,
            'email' => $email,
        ], ['name']);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name) VALUES (?, ?) "
        . "ON CONFLICT (name) DO UPDATE SET name = excluded.name";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$email, $name]);
});

it('generates upsert statement to handle duplicate keys with many unique columns', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $data = [
        'name' => faker()->name,
        'username' => faker()->userName,
        'email' => faker()->freeEmail,
    ];

    $sql = $query->table('users')
        ->upsert($data, ['name', 'username']);

    [$dml, $params] = $sql;

    $expected = "INSERT INTO users (email, name, username) VALUES (?, ?, ?) "
        . "ON CONFLICT (name, username) DO UPDATE SET name = excluded.name, username = excluded.username";

    \ksort($data);

    expect($dml)->toBe($expected);
    expect($params)->toBe(\array_values($data));
});

it('generates insert statement from subquery', function () {
    $query = new QueryGenerator(Driver::SQLITE);

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
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->insertFrom(function (Subquery $subquery) {
            $subquery->table('customers')
                ->select(['name', 'email'])
                ->whereNotNull('verified_at');
        }, ['name', 'email'], true);

    [$dml, $params] = $sql;

    $expected = "INSERT OR IGNORE INTO users (name, email) "
        . "SELECT name, email FROM customers WHERE verified_at IS NOT NULL";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});
