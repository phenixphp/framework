<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Models;

use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\DateTime;
use Phenix\Database\Models\Attributes\Hidden;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Util\Date;

class SecureUser extends DatabaseModel
{
    #[Id]
    public int $id;

    #[Column]
    public string $name;

    #[Hidden]
    public string $password;

    #[DateTime(name: 'created_at', autoInit: true)]
    public Date $createdAt;

    public static function table(): string
    {
        return 'secure_users';
    }
}
