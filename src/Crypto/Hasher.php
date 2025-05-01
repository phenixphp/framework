<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Hasher as HasherContract;
use SensitiveParameter;

class Hasher implements HasherContract
{
    public function make(#[SensitiveParameter] string $password): string
    {
        return sodium_crypto_pwhash_str(
            $password,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
    }

    public function verify(string $hash, #[SensitiveParameter] string $password): bool
    {
        return sodium_crypto_pwhash_str_verify($hash, $password);
    }

    public function needsRehash(string $hash): bool
    {
        return sodium_crypto_pwhash_str_needs_rehash(
            $hash,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
    }
}
