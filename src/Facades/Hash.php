<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Crypto\Contracts\Hasher;
use Phenix\Runtime\Facade;

/**
 * @method static string make(string $password): string
 * @method static bool verify(string $hash, string $password): bool
 * @method static bool needsRehash(string $hash): bool
 *
 * @see \Phenix\Crypto\Hash
 */
class Hash extends Facade
{
    public static function getKeyName(): string
    {
        return Hasher::class;
    }
}
