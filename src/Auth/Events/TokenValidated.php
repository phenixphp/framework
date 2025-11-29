<?php

declare(strict_types=1);

namespace Phenix\Auth\Events;

use Phenix\Auth\PersonalAccessToken;
use Phenix\Events\AbstractEvent;
use Phenix\Http\Request;

use function count;
use function in_array;

class TokenValidated extends AbstractEvent
{
    public function __construct(PersonalAccessToken $token, Request $request, string|null $clientIp)
    {
        $abilities = $token->getAbilities() ?? [];

        $this->payload = [
            'token_id' => $token->id,
            'user_id' => $token->tokenableId,
            'user_type' => $token->tokenableType,
            'abilities_count' => count($abilities),
            'wildcard' => in_array('*', $abilities, true),
            'expires_at' => $token->expiresAt?->toDateTimeString(),
            'request_path' => $request->getUri()->getPath(),
            'request_method' => $request->getMethod(),
            'client_ip' => $clientIp,
        ];
    }
}
