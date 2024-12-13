<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\HasMany;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\Collection;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class User extends DatabaseModel
{
    #[Id]
    public int $id;

    #[Column]
    public string $name;

    #[Column]
    public string $email;

    #[Column(name: 'created_at')]
    public Date $createdAt;

    #[Column(name: 'updated_at')]
    public Date|null $updatedAt = null;

    #[HasMany(model: Product::class, foreignKey: 'user_id')]
    public Collection $products;

    #[HasMany(model: Comment::class, foreignKey: 'user_id', chaperone: true)]
    public Collection $comments;

    public static function table(): string
    {
        return 'users';
    }
}
