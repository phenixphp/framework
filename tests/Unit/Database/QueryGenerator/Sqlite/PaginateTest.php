<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\Order;
use Phenix\Database\QueryGenerator;

it('generates offset pagination query', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->page()
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" LIMIT 15 OFFSET 0');
    expect($params)->toBeEmpty();
});

it('generates offset pagination query with indicate page', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->page(3)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" LIMIT 15 OFFSET 30');
    expect($params)->toBeEmpty();
});

it('overwrites limit when pagination is called', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->limit(5)
        ->page(2)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" LIMIT 15 OFFSET 15');
    expect($params)->toBeEmpty();
});

it('generates pagination query with where clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->whereEqual('status', 'active')
        ->page(2)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" WHERE "status" = ? LIMIT 15 OFFSET 15');
    expect($params)->toBe(['active']);
});

it('generates pagination query with order by', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->orderBy('created_at', Order::ASC)
        ->page(1)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" ORDER BY "created_at" ASC LIMIT 15 OFFSET 0');
    expect($params)->toBeEmpty();
});

it('generates pagination with custom per page', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->table('users')
        ->page(2, 25)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM "users" LIMIT 25 OFFSET 25');
    expect($params)->toBeEmpty();
});
