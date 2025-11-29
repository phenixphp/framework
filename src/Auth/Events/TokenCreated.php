<?php

declare(strict_types=1);

namespace Phenix\Auth\Events;

use Phenix\Auth\PersonalAccessToken;
use Phenix\Events\AbstractEvent;

class TokenCreated extends AbstractEvent
{
    public function __construct(PersonalAccessToken $token)
    {
        $this->payload = [
            'token_id' => $token->id,
            'user_id' => $token->tokenableId,
            'user_type' => $token->tokenableType,
            'name' => $token->name,
            'abilities' => $token->getAbilities(),
            'expires_at' => $token->expiresAt->toDateTimeString(),
            'created_at' => $token->createdAt->toDateTimeString(),
        ];
    }
}
