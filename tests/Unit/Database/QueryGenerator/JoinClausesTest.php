<?php

declare(strict_types=1);

use Phenix\Database\Constants\JoinType;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates query for all join types', function (string $method, string $joinType) {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'products.description',
            'categories.description',
        ])
        ->from('products')
        ->{$method}('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `products`.`description`, `categories`.`description` "
        . "FROM `products` "
        . "{$joinType} `categories` "
        . "ON `products`.`category_id` = `categories`.`id`";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['innerJoin', JoinType::INNER->value],
    ['leftJoin', JoinType::LEFT->value],
    ['leftOuterJoin', JoinType::LEFT_OUTER->value],
    ['rightJoin', JoinType::RIGHT->value],
    ['rightOuterJoin', JoinType::RIGHT_OUTER->value],
    ['crossJoin', JoinType::CROSS->value],
]);

it('generates query using join with distinct clasue', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'products.description',
            'categories.description',
        ])
        ->from('products')
        ->innerJoin('categories', function (Join $join) {
            $join->onNotEqual('products.category_id', 'categories.id');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `products`.`description`, `categories`.`description` "
        . "FROM `products` "
        . "INNER JOIN `categories` "
        . "ON `products`.`category_id` != `categories`.`id`";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query with join and multi clauses', function (
    string $chainingMethod,
    array $arguments,
    string $clause,
    array|null $joinParams
) {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'products.description',
            'categories.description',
        ])
        ->from('products')
        ->innerJoin('categories', function (Join $join) use ($chainingMethod, $arguments) {
            $join->onEqual('products.category_id', 'categories.id')
                ->$chainingMethod(...$arguments);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `products`.`description`, `categories`.`description` "
        . "FROM `products` "
        . "INNER JOIN `categories` "
        . "ON `products`.`category_id` = `categories`.`id` {$clause}";

    expect($dml)->toBe($expected);
    expect($params)->toBe($joinParams);
})->with([
    [
        'orOnEqual',
        ['products.location_id', 'categories.location_id'],
        'OR `products`.`location_id` = `categories`.`location_id`',
        [],
    ],
    [
        'whereEqual',
        ['categories.name', 'php'],
        'AND `categories`.`name` = ?',
        ['php'],
    ],
    [
        'orOnNotEqual',
        ['products.location_id', 'categories.location_id'],
        'OR `products`.`location_id` != `categories`.`location_id`',
        [],
    ],
    [
        'orWhereEqual',
        ['categories.name', 'php'],
        'OR `categories`.`name` = ?',
        ['php'],
    ],
]);

it('generates query with shortcut methods for all join types', function (string $method, string $joinType) {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'products.description',
            'categories.description',
        ])
        ->from('products')
        ->{$method}('categories', 'products.category_id', 'categories.id')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `products`.`description`, `categories`.`description` "
        . "FROM `products` "
        . "{$joinType} `categories` "
        . "ON `products`.`category_id` = `categories`.`id`";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['innerJoinOnEqual', JoinType::INNER->value],
    ['leftJoinOnEqual', JoinType::LEFT->value],
    ['rightJoinOnEqual', JoinType::RIGHT->value],
]);

it('generates query with join date clause', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'categories.description',
        ])
        ->from('products')
        ->innerJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id')
                ->whereDateEqual('categories.created_at', '2026-01-15');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `categories`.`description` "
        . "FROM `products` "
        . "INNER JOIN `categories` "
        . "ON `products`.`category_id` = `categories`.`id` AND DATE(`categories`.`created_at`) = ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['2026-01-15']);
});

it('generates query with multiple joins using params', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'products.id',
            'categories.name',
            'suppliers.name',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id')
                ->whereEqual('categories.status', 'active');
        })
        ->leftJoin('suppliers', function (Join $join) {
            $join->onEqual('products.supplier_id', 'suppliers.id')
                ->whereEqual('suppliers.region', 'latam');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT `products`.`id`, `categories`.`name`, `suppliers`.`name` "
        . "FROM `products` "
        . "LEFT JOIN `categories` ON `products`.`category_id` = `categories`.`id` AND `categories`.`status` = ? "
        . "LEFT JOIN `suppliers` ON `products`.`supplier_id` = `suppliers`.`id` AND `suppliers`.`region` = ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active', 'latam']);
});
