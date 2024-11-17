<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\Attributes\BelongsToMany;
use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\ForeignKey;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class Invoice extends DatabaseModel
{
    #[Id]
    public int $id;

    #[Column]
    public string $reference;

    #[Column]
    public float $value;

    #[ForeignKey(name: 'user_id')]
    public int $userId;

    #[Column(name: 'created_at')]
    public Date $createdAt;

    #[Column(name: 'updated_at')]
    public Date|null $updatedAt = null;

    #[BelongsTo('userId')]
    public User $user;

    #[BelongsToMany(
        table: 'invoice_product',
        foreignKey: 'invoice_id',
        relatedModel: Product::class,
        relatedForeignKey: 'product_id'
    )]
    public Collection $products;

    public static function table(): string
    {
        return 'products';
    }
}
