<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Functions;
use Phenix\Database\Having;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates a query using having clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Functions::count('products.id')->as('identifiers'),
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy('products.category_id')
        ->having(function (Having $having): void {
            $having->whereGreaterThan('identifiers', 5);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id) AS identifiers, products.category_id, categories.description "
        . "FROM products "
        . "LEFT JOIN categories ON products.category_id = categories.id "
        . "HAVING identifiers > ? GROUP BY products.category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5]);
});

it('generates a query using having with many clauses', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Functions::count('products.id')->as('identifiers'),
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy('products.category_id')
        ->having(function (Having $having): void {
            $having->whereGreaterThan('identifiers', 5)
                ->whereGreaterThan('products.category_id', 10);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id) AS identifiers, products.category_id, categories.description "
        . "FROM products "
        . "LEFT JOIN categories ON products.category_id = categories.id "
        . "HAVING identifiers > ? AND products.category_id > ? GROUP BY products.category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5, 10]);
});

it('generates a query using having with where clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Functions::count('products.id')->as('product_count'),
            'products.category_id',
        ])
        ->from('products')
        ->whereEqual('products.status', 'active')
        ->groupBy('products.category_id')
        ->having(function (Having $having): void {
            $having->whereGreaterThan('product_count', 3);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id) AS product_count, products.category_id "
        . "FROM products "
        . "WHERE products.status = ? "
        . "HAVING product_count > ? GROUP BY products.category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active', 3]);
});

it('generates a query using having with less than', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Functions::sum('orders.total')->as('total_sales'),
            'orders.customer_id',
        ])
        ->from('orders')
        ->groupBy('orders.customer_id')
        ->having(function (Having $having): void {
            $having->whereLessThan('total_sales', 1000);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT SUM(orders.total) AS total_sales, orders.customer_id "
        . "FROM orders "
        . "HAVING total_sales < ? GROUP BY orders.customer_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1000]);
});

it('generates a query using having with equal', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Functions::count('products.id')->as('product_count'),
            'products.category_id',
        ])
        ->from('products')
        ->groupBy('products.category_id')
        ->having(function (Having $having): void {
            $having->whereEqual('product_count', 10);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(products.id) AS product_count, products.category_id "
        . "FROM products "
        . "HAVING product_count = ? GROUP BY products.category_id";

    expect($dml)->toBe($expected);
    expect($params)->toBe([10]);
});
