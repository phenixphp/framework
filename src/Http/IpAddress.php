<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Request;

final class IpAddress
{
    private function __construct()
    {
        // Prevent instantiation
    }

    public static function parse(Request $request): string|null
    {
        $xff = $request->getHeader('X-Forwarded-For');

        if ($xff && $ip = self::getFromHeader($xff)) {
            return $ip;
        }

        $ip = (string) $request->getClient()->getRemoteAddress();

        if ($ip !== '') {
            return explode(':', $ip)[0] ?? null;
        }

        return null;
    }

    private static function getFromHeader(string $header): string
    {
        $parts = explode(',', $header)[0] ?? '';

        return trim($parts);
    }
}
