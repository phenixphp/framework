<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Database\Models\Attributes\Column;
use Phenix\Database\Models\Attributes\DateTime;
use Phenix\Database\Models\Attributes\Hidden;
use Phenix\Database\Models\Attributes\Id;
use Phenix\Database\Models\DatabaseModel;
use Phenix\Database\Models\QueryBuilders\DatabaseQueryBuilder;
use Phenix\Util\Date;

class PersonalAccessToken extends DatabaseModel
{
    #[Id]
    public string $id;

    #[Column(name: 'tokenable_type')]
    public string $tokenableType;

    #[Column(name: 'tokenable_id')]
    public int $tokenableId;

    #[Column]
    public string $name;

    #[Hidden]
    public string $token;

    #[Column]
    public string|null $abilities = null;

    #[DateTime(name: 'last_used_at')]
    public Date|null $lastUsedAt = null;

    #[DateTime(name: 'expires_at')]
    public Date|null $expiresAt = null;

    #[DateTime(name: 'created_at', autoInit: true)]
    public Date $createdAt;

    #[DateTime(name: 'updated_at')]
    public Date|null $updatedAt = null;

    public static function table(): string
    {
        return 'personal_access_tokens';
    }

    protected static function newQueryBuilder(): DatabaseQueryBuilder
    {
        return new PersonalAccessTokenQuery();
    }
}
