<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\DateTime;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class UserWithUuid extends DatabaseModel
{
    #[Id]
    public string $id;

    #[Column]
    public string $name;

    #[Column]
    public string $email;

    #[DateTime(name: 'created_at', autoInit: true)]
    public Date $createdAt;

    #[DateTime(name: 'updated_at')]
    public Date|null $updatedAt = null;

    public static function table(): string
    {
        return 'uuid_users';
    }
}
