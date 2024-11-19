<?php

declare(strict_types=1);

use Phenix\Database\Models\Attributes\BelongsToMany as BelongsToManyAttr;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\Properties\BelongsToManyProperty;
use Phenix\Database\Models\Relationships\BelongsToMany;
use Tests\Feature\Database\Models\Product;

it('generates intermediate columns', function () {
    $property = new BelongsToManyProperty(
        'products',
        Collection::class,
        true,
        new BelongsToManyAttr(
            table: 'invoice_product',
            foreignKey: 'invoice_id',
            relatedModel: Product::class,
            relatedForeignKey: 'product_id'
        ),
        null
    );

    $relationship = new BelongsToMany($property);

    $relationship->withPivot(['amount', 'value']);

    expect($relationship->getColumns())->toBe([
        'invoice_product.invoice_id' => 'pivot_invoice_id',
        'invoice_product.product_id' => 'pivot_product_id',
        'invoice_product.amount' => 'pivot_amount',
        'invoice_product.value' => 'pivot_value',
    ]);
});
