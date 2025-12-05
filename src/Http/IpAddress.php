<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Request;
use Phenix\Crypto\Bin2Base64;
use Phenix\Facades\Config;

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
        $host = parse_url($ip, PHP_URL_HOST);

        if ($host === null) {
            return $ip;
        }

        $encodedKey = Config::get('app.key');

        if ($encodedKey) {
            $decodedKey = Bin2Base64::decode($encodedKey);

            return hash_hmac('sha256', $host, $decodedKey);
        }

        return hash('sha256', $host);
    }

    private static function getFromHeader(string $header): string
    {
        $parts = explode(',', $header)[0] ?? '';

        return trim($parts);
    }
}
