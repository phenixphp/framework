<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\BelongsTo;
use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\ForeignKey;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class Comment extends DatabaseModel
{
    #[Id]
    public int $id;

    #[Column]
    public string $content;

    #[ForeignKey(name: 'user_id')]
    public int $userId;


    #[ForeignKey(name: 'product_id')]
    public int $productId;

    #[Column(name: 'created_at')]
    public Date $createdAt;

    #[Column(name: 'updated_at')]
    public Date|null $updatedAt = null;

    #[BelongsTo('userId')]
    public User $user;

    #[BelongsTo('productId')]
    public Product $product;

    public static function table(): string
    {
        return 'comments';
    }
}
