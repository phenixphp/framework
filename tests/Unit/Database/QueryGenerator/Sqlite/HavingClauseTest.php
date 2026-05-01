<?php

declare(strict_types=1);

use Phenix\Database\Constants\Driver;
use Phenix\Database\Funct;
use Phenix\Database\Having;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates a query using having clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::count('products.id')->as('identifiers'),
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

    $expected = "SELECT COUNT(\"products\".\"id\") AS \"identifiers\", \"products\".\"category_id\", \"categories\".\"description\" "
        . "FROM \"products\" "
        . "LEFT JOIN \"categories\" ON \"products\".\"category_id\" = \"categories\".\"id\" "
        . "GROUP BY \"products\".\"category_id\" HAVING \"identifiers\" > ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5]);
});

it('generates a query using having with many clauses', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::count('products.id')->as('identifiers'),
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

    $expected = "SELECT COUNT(\"products\".\"id\") AS \"identifiers\", \"products\".\"category_id\", \"categories\".\"description\" "
        . "FROM \"products\" "
        . "LEFT JOIN \"categories\" ON \"products\".\"category_id\" = \"categories\".\"id\" "
        . "GROUP BY \"products\".\"category_id\" HAVING \"identifiers\" > ? AND \"products\".\"category_id\" > ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5, 10]);
});

it('generates a query using having with where clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::count('products.id')->as('product_count'),
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

    $expected = "SELECT COUNT(\"products\".\"id\") AS \"product_count\", \"products\".\"category_id\" "
        . "FROM \"products\" "
        . "WHERE \"products\".\"status\" = ? "
        . "GROUP BY \"products\".\"category_id\" HAVING \"product_count\" > ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['active', 3]);
});

it('generates a query using having with less than', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::sum('orders.total')->as('total_sales'),
            'orders.customer_id',
        ])
        ->from('orders')
        ->groupBy('orders.customer_id')
        ->having(function (Having $having): void {
            $having->whereLessThan('total_sales', 1000);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT SUM(\"orders\".\"total\") AS \"total_sales\", \"orders\".\"customer_id\" "
        . "FROM \"orders\" "
        . "GROUP BY \"orders\".\"customer_id\" HAVING \"total_sales\" < ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1000]);
});

it('generates a query using having with equal', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::count('products.id')->as('product_count'),
            'products.category_id',
        ])
        ->from('products')
        ->groupBy('products.category_id')
        ->having(function (Having $having): void {
            $having->whereEqual('product_count', 10);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(\"products\".\"id\") AS \"product_count\", \"products\".\"category_id\" "
        . "FROM \"products\" "
        . "GROUP BY \"products\".\"category_id\" HAVING \"product_count\" = ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([10]);
});

it('generates a query using having with date clause', function () {
    $query = new QueryGenerator(Driver::SQLITE);

    $sql = $query->select([
            Funct::count('products.id')->as('product_count'),
            'products.created_at',
        ])
        ->from('products')
        ->groupBy('products.created_at')
        ->having(function (Having $having): void {
            $having->whereDateEqual('products.created_at', '2026-01-15');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(\"products\".\"id\") AS \"product_count\", \"products\".\"created_at\" "
        . "FROM \"products\" "
        . "GROUP BY \"products\".\"created_at\" HAVING DATE(\"products\".\"created_at\") = ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['2026-01-15']);
});
