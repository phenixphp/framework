<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\ForeignKey;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class Product extends DatabaseModel
{
    #[Id]
    public int $id;

    #[Column]
    public string $description;

    #[Column]
    public float $price;

    #[Column]
    public int $stock;

    #[ForeignKey(name: 'user_id')]
    public int $userId;

    #[Column(name: 'created_at')]
    public Date $createdAt;

    #[Column(name: 'updated_at')]
    public Date|null $updatedAt = null;

    #[BelongsTo('userId')]
    public User $user;

    public static function table(): string
    {
        return 'products';
    }
}
