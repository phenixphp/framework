<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Routing\UrlGenerator;
use Phenix\Runtime\Facade;

/**
 * @method static string signedRoute(\BackedEnum|string $name, mixed $parameters = [], \DateTimeInterface|\DateInterval|int|null $expiration = null, bool $absolute = true)
 * @method static string temporarySignedRoute(\BackedEnum|string $name, \DateTimeInterface|\DateInterval|int $expiration, array $parameters = [], bool $absolute = true)
 * @method static bool hasValidSignature(\Amp\Http\Server\Request $request, bool $absolute = true, \Closure|array $ignoreQuery = [])
 * @method static bool signatureHasNotExpired(\Amp\Http\Server\Request $request)
 * @method static string route(\BackedEnum|string $name, mixed $parameters = [], bool $absolute = true)
 * @method static string secure(string $path, array $parameters = [])
 *
 * @see \Phenix\Routing\UrlGenerator
 */
class Url extends Facade
{
    public static function getKeyName(): string
    {
        return UrlGenerator::class;
    }
}
