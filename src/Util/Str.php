<?php

declare(strict_types=1);

namespace Phenix\Util;

use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

class Str extends Utility
{
    public static function snake(string $value): string
    {
        $pattern = '/([a-z])([A-Z])/';

        $replacement = '$1_$2';

        return strtolower(preg_replace($pattern, $replacement, $value));
    }

    public static function uuid(): UuidV4
    {
        return Uuid::v4();
    }

    public static function isUuid(string $uuid): bool
    {
        return Uuid::isValid($uuid);
    }

    public static function ulid(): Ulid
    {
        return new Ulid();
    }

    public static function isUlid(string $ulid): bool
    {
        return Ulid::isValid($ulid);
    }

    public static function start(string $string, string $prefix): string
    {
        if (str_starts_with($string, $prefix)) {
            return $string;
        }

        return "{$prefix}{$string}";
    }

    public static function finish(string $string, string $suffix): string
    {
        if (str_ends_with($string, $suffix)) {
            return $string;
        }

        return "{$string}{$suffix}";
    }

    public static function slug(string $value, string $separator = '-'): string
    {
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', '', $value);

        return strtolower(preg_replace('/[\s]/u', $separator, $value));
    }
}
