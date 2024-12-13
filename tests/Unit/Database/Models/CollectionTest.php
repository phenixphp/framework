<?php

declare(strict_types=1);

use Phenix\Database\Models\Collection;
use Phenix\Util\Date;
use Tests\Feature\Database\Models\Product;

it('can convert a collection of DatabaseModels to an array', function () {
    $product1 = new Product();
    $product1->id = 1;
    $product1->description = 'Product 1';
    $product1->price = 10.0;
    $product1->stock = 100;
    $product1->userId = 1;
    $product1->createdAt = new Date('2023-01-01');
    $product1->updatedAt = new Date('2023-01-02');

    $product2 = new Product();
    $product2->id = 2;
    $product2->description = 'Product 2';
    $product2->price = 20.0;
    $product2->stock = 200;
    $product2->userId = 2;
    $product2->createdAt = new Date('2023-01-03');
    $product2->updatedAt = new Date('2023-01-04');

    $collection = new Collection([$product1, $product2]);

    $expected = [
        [
            'id' => 1,
            'description' => 'Product 1',
            'price' => 10.0,
            'stock' => 100,
            'userId' => 1,
            'createdAt' => '2023-01-01T00:00:00+00:00',
            'updatedAt' => '2023-01-02T00:00:00+00:00',
        ],
        [
            'id' => 2,
            'description' => 'Product 2',
            'price' => 20.0,
            'stock' => 200,
            'userId' => 2,
            'createdAt' => '2023-01-03T00:00:00+00:00',
            'updatedAt' => '2023-01-04T00:00:00+00:00',
        ],
    ];

    expect($collection->toArray())->toBe($expected);
});
