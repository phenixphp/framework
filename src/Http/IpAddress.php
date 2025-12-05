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

    public static function parse(Request $request): string
    {
        $xff = $request->getHeader('X-Forwarded-For');

        if ($xff && $ip = self::getFromHeader($xff)) {
            return $ip;
        }

        return (string) $request->getClient()->getRemoteAddress();
    }

    public static function hash(Request $request): string
    {
        $ip = self::parse($request);

        $normalized = self::normalize($ip);

        return hash('sha256', $normalized);
    }

    private static function getFromHeader(string $header): string
    {
        $parts = explode(',', $header)[0] ?? '';

        return trim($parts);
    }

    private static function normalize(string $ip): string
    {
        if (preg_match('/^\[(?<addr>[^\]]+)\](?::\d+)?$/', $ip, $m) === 1) {
            return $m['addr'];
        }

        $normalized = $ip;

        if (filter_var($normalized, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $normalized;
        }

        if (str_contains($normalized, ':')) {
            $parts = explode(':', $normalized);
            $maybeIpv4 = $parts[0];

            if (filter_var($maybeIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $normalized = $maybeIpv4;
            }
        }

        return $normalized;
    }
}
