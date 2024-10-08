<?php

declare(strict_types=1);

use Phenix\Database\QueryGenerator;

it('generates offset pagination query', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->page()
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users LIMIT 15 OFFSET 0');
    expect($params)->toBeEmpty();
});

it('generates offset pagination query with indicate page', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->page(3)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users LIMIT 15 OFFSET 30');
    expect($params)->toBeEmpty();
});

it('overwrites limit when pagination is called', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->limit(5)
        ->page(2)
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users LIMIT 15 OFFSET 15');
    expect($params)->toBeEmpty();
});
