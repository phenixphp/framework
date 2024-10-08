<?php

declare(strict_types=1);

use Phenix\Database\Functions;
use Phenix\Database\Having;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates a query using having clause', function () {
    $query = new QueryGenerator();

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
    $query = new QueryGenerator();

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
