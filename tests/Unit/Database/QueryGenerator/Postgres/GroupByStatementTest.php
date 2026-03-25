<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Functions;
use Phenix\Database\Having;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates a grouped query', function (Functions|string $column, Functions|array|string $groupBy, string $rawGroup): void {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->select([
            $column,
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join): void {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy($groupBy)
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT {$column}, products.category_id, categories.description "
        . "FROM products "
        . "LEFT JOIN categories ON products.category_id = categories.id "
        . "GROUP BY {$rawGroup}";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    [Functions::count('products.id'), 'category_id', 'category_id'],
    ['location_id', ['category_id', 'location_id'], 'category_id, location_id'],
    [Functions::count('products.id'), Functions::count('products.id'), 'COUNT(products.id)'],
]);

it('generates a grouped and ordered query', function (
    Functions|string $column,
    Functions|array|string $groupBy,
    string $rawGroup
) {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->select([
            $column,
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join): void {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy($groupBy)
        ->orderBy('products.id')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT {$column}, products.category_id, categories.description "
        . "FROM products "
        . "LEFT JOIN categories ON products.category_id = categories.id "
        . "GROUP BY {$rawGroup} "
        . "ORDER BY products.id DESC";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    [Functions::count('products.id'), 'category_id', 'category_id'],
    ['location_id', ['category_id', 'location_id'], 'category_id, location_id'],
    [Functions::count('products.id'), Functions::count('products.id'), 'COUNT(products.id)'],
]);

it('generates a grouped query with where clause', function (): void {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->select([
            Functions::count('products.id'),
            'products.category_id',
        ])
        ->from('products')
        ->whereEqual('products.status', 'active')
        ->groupBy('category_id')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id), products.category_id "
        . "FROM products "
        . "WHERE products.status = $1 "
        . "GROUP BY category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active']);
});

it('generates a grouped query with having clause', function (): void {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->select([
            Functions::count('products.id')->as('product_count'),
            'products.category_id',
        ])
        ->from('products')
        ->groupBy('category_id')
        ->having(function (Having $having): void {
            $having->whereGreaterThan('product_count', 5);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id) AS product_count, products.category_id "
        . "FROM products "
        . "HAVING product_count > $1 "
        . "GROUP BY category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5]);
});

it('generates a grouped query with multiple aggregations', function (): void {
    $query = new QueryGenerator(Driver::POSTGRESQL);

    $sql = $query->select([
            Functions::count('products.id'),
            Functions::sum('products.price'),
            Functions::avg('products.price'),
            'products.category_id',
        ])
        ->from('products')
        ->groupBy('category_id')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id), SUM(products.price), AVG(products.price), products.category_id "
        . "FROM products "
        . "GROUP BY category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});
