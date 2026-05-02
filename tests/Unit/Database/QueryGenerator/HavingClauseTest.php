<?php

declare(strict_types=1);

use Phenix\Database\Having;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

use function Phenix\Database\count_of;

it('generates a query using having clause', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            count_of('products.id')->as('identifiers'),
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

    $expected = "SELECT COUNT(`products`.`id`) AS `identifiers`, `products`.`category_id`, `categories`.`description` "
        . "FROM `products` "
        . "LEFT JOIN `categories` ON `products`.`category_id` = `categories`.`id` "
        . "GROUP BY `products`.`category_id` HAVING `identifiers` > ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5]);
});

it('generates a query using having with many clauses', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            count_of('products.id')->as('identifiers'),
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

    $expected = "SELECT COUNT(`products`.`id`) AS `identifiers`, `products`.`category_id`, `categories`.`description` "
        . "FROM `products` "
        . "LEFT JOIN `categories` ON `products`.`category_id` = `categories`.`id` "
        . "GROUP BY `products`.`category_id` HAVING `identifiers` > ? AND `products`.`category_id` > ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe([5, 10]);
});

it('generates a query using having with date clause', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            count_of('products.id')->as('product_count'),
            'products.created_at',
        ])
        ->from('products')
        ->groupBy('products.created_at')
        ->having(function (Having $having): void {
            $having->whereDateEqual('products.created_at', '2026-01-15');
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(`products`.`id`) AS `product_count`, `products`.`created_at` "
        . "FROM `products` "
        . "GROUP BY `products`.`created_at` HAVING DATE(`products`.`created_at`) = ?";

    expect($dml)->toBe($expected);
    expect($params)->toBe(['2026-01-15']);
});
