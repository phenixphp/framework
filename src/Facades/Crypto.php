<?php

declare(strict_types=1);

namespace Phenix\Facades;

use Phenix\Crypto\Bin2Base64;
use Phenix\Crypto\Crypto as CryptoService;
use Phenix\Runtime\Facade;

/**
 * @method static string encrypt(object|array|string $value, bool $serialize = true)
 * @method static string encryptString(string $value)
 * @method static object|array|string decrypt(string $payload, bool $unserialize = true)
 * @method static object|array|string decryptString(string $payload, bool $unserialize = true)
 *
 * @see \Phenix\Crypto\Crypto
 */
class Crypto extends Facade
{
    public static function getKeyName(): string
    {
        return CryptoService::class;
    }

    public static function generateKey(): string
    {
        return sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
    }

    public static function generateEncodedKey(): string
    {
        return Bin2Base64::encode(self::generateKey());
    }
}
