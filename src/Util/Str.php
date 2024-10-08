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
}
