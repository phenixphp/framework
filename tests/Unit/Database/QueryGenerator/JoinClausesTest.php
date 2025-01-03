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

    $expected = "SELECT products.id, products.description, categories.description "
        . "FROM products "
        . "{$joinType} categories "
        . "ON products.category_id = categories.id";

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
            $join->onDistinct('products.category_id', 'categories.id');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT products.id, products.description, categories.description "
        . "FROM products "
        . "INNER JOIN categories "
        . "ON products.category_id != categories.id";

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

    $expected = "SELECT products.id, products.description, categories.description "
        . "FROM products "
        . "INNER JOIN categories "
        . "ON products.category_id = categories.id {$clause}";

    expect($dml)->toBe($expected);
    expect($params)->toBe($joinParams);
})->with([
    [
        'orOnEqual',
        ['products.location_id', 'categories.location_id'],
        'OR products.location_id = categories.location_id',
        [],
    ],
    [
        'whereEqual',
        ['categories.name', 'php'],
        'AND categories.name = ?',
        ['php'],
    ],
    [
        'orOnDistinct',
        ['products.location_id', 'categories.location_id'],
        'OR products.location_id != categories.location_id',
        [],
    ],
    [
        'orWhereEqual',
        ['categories.name', 'php'],
        'OR categories.name = ?',
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

    $expected = "SELECT products.id, products.description, categories.description "
        . "FROM products "
        . "{$joinType} categories "
        . "ON products.category_id = categories.id";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['innerJoinOnEqual', JoinType::INNER->value],
    ['leftJoinOnEqual', JoinType::LEFT->value],
    ['rightJoinOnEqual', JoinType::RIGHT->value],
]);
