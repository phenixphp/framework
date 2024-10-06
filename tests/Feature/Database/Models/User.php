<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\Properties\Column;
use Phenix\Database\Models\Properties\Id;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
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
    public Date|null $updatedAt;

    public static function table(): string
    {
        return 'users';
    }

    protected static function newQueryBuilder(): DatabaseQueryBuilder
    {
        return new UserQuery();
    }
}
