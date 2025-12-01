<?php

declare(strict_types=1);

namespace Phenix\Auth\Events;

use Phenix\Auth\AuthenticationToken;
use Phenix\Auth\PersonalAccessToken;
use Phenix\Events\AbstractEvent;

class TokenRefreshCompleted extends AbstractEvent
{
    public function __construct(PersonalAccessToken $previous, AuthenticationToken $newToken)
    {
        $this->payload = [
            'previous_token_id' => $previous->id,
            'user_id' => $previous->tokenableId,
            'user_type' => $previous->tokenableType,
            'previous_expires_at' => $previous->expiresAt->toDateTimeString(),
            'new_token_id' => $newToken->id(),
            'new_expires_at' => $newToken->expiresAt(),
        ];
    }
}
