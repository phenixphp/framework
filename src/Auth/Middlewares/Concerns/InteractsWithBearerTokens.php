<?php

declare(strict_types=1);

namespace Phenix\Auth\Middlewares\Concerns;

trait InteractsWithBearerTokens
{
    protected function hasBearerScheme(string|null $authorizationHeader): bool
    {
        if ($authorizationHeader === null) {
            return false;
        }

        $authorizationHeader = trim($authorizationHeader);

        if ($authorizationHeader === '') {
            return false;
        }

        return preg_match('/^Bearer(?:\s+.*)?$/i', $authorizationHeader) === 1;
    }

    protected function extractBearerToken(string|null $authorizationHeader): string|null
    {
        if (! $this->hasBearerScheme($authorizationHeader)) {
            return null;
        }

        $authorizationHeader = trim((string) $authorizationHeader);

        if (preg_match('/^Bearer\s+([A-Za-z0-9._~+\\/-]+=*)$/i', $authorizationHeader, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]) !== '' ? $matches[1] : null;
    }
}
