<?php

declare(strict_types=1);

namespace Phenix\Auth\Events;

use Phenix\Events\AbstractEvent;
use Phenix\Http\Request;

use function strlen;

class FailedTokenValidation extends AbstractEvent
{
    public function __construct(Request $request, string|null $clientIp, string $reason, string|null $attemptedToken = null, int|null $attemptCount = null)
    {
        $this->payload = [
            'reason' => $reason,
            'attempted_token_length' => $attemptedToken !== null ? strlen($attemptedToken) : 0,
            'client_ip' => $clientIp,
            'request_path' => $request->getUri()->getPath(),
            'request_method' => $request->getMethod(),
            'attempt_count' => $attemptCount,
        ];
    }
}
