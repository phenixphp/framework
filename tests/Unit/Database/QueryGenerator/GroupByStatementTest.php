<?php

declare(strict_types=1);

use Phenix\Database\Funct;
use Phenix\Database\Join;
use Phenix\Database\QueryGenerator;

it('generates a grouped query', function (Funct|string $column, Funct|array|string $groupBy, string $rawGroup, string $rawColumn) {
    $query = new QueryGenerator();

    $sql = $query->select([
            $column,
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy($groupBy)
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT {$rawColumn}, `products`.`category_id`, `categories`.`description` "
        . "FROM `products` "
        . "LEFT JOIN `categories` ON `products`.`category_id` = `categories`.`id` "
        . "GROUP BY {$rawGroup}";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    [Funct::count('products.id'), 'category_id', '`category_id`', 'COUNT(`products`.`id`)'],
    ['location_id', ['category_id', 'location_id'], '`category_id`, `location_id`', '`location_id`'],
    [Funct::count('products.id'), Funct::count('products.id'), 'COUNT(`products`.`id`)', 'COUNT(`products`.`id`)'],
]);

it('generates a grouped and ordered query', function (
    Funct|string $column,
    Funct|array|string $groupBy,
    string $rawGroup,
    string $rawColumn
) {
    $query = new QueryGenerator();

    $sql = $query->select([
            $column,
            'products.category_id',
            'categories.description',
        ])
        ->from('products')
        ->leftJoin('categories', function (Join $join) {
            $join->onEqual('products.category_id', 'categories.id');
        })
        ->groupBy($groupBy)
        ->orderBy('products.id')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT {$rawColumn}, `products`.`category_id`, `categories`.`description` "
        . "FROM `products` "
        . "LEFT JOIN `categories` ON `products`.`category_id` = `categories`.`id` "
        . "GROUP BY {$rawGroup} "
        . "ORDER BY `products`.`id` DESC";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    [Funct::count('products.id'), 'category_id', '`category_id`', 'COUNT(`products`.`id`)'],
    ['location_id', ['category_id', 'location_id'], '`category_id`, `location_id`', '`location_id`'],
    [Funct::count('products.id'), Funct::count('products.id'), 'COUNT(`products`.`id`)', 'COUNT(`products`.`id`)'],
]);
