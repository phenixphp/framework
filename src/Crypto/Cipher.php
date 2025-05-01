<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Cipher as CipherContract;
use Phenix\Crypto\Exceptions\DecryptException;
use Phenix\Crypto\Exceptions\EncryptException;
use SensitiveParameter;

class Cipher implements CipherContract
{
    protected string|null $previousKey;

    public function __construct(
        protected string $key
    ) {
        $this->previousKey = null;
    }

    public function encrypt(#[SensitiveParameter] object|array|string $value, bool $serialize = true): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $output = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            message: $serialize ? serialize($value) : $value,
            additional_data: '',
            nonce: $nonce,
            key: $this->parseKey($this->key)
        );

        if (! $output) {
            throw new EncryptException();
        }

        return sodium_bin2base64("{$nonce}{$output}", SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public function decrypt(string $payload, bool $unserialize = true): object|array|string
    {
        $payload = sodium_base642bin($payload, SODIUM_BASE64_VARIANT_ORIGINAL);

        $nonce = substr($payload, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = substr($payload, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $decrypted = false;

        foreach ($this->getAllKeys() as $key) {
            $decrypted = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
                $cipherText,
                '',
                $nonce,
                $key
            );

            if ($decrypted !== false) {
                break;
            }
        }

        if ($decrypted === false) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $unserialize ? unserialize($decrypted) : $decrypted;
    }

    protected function parseKey(string $key): string
    {
        if (str_contains($key, ':')) {
            return Bin2Base64::decode($key);
        }

        return $key;
    }

    protected function getAllKeys(): array
    {
        $keys = [$this->parseKey($this->key)];

        if ($this->previousKey) {
            $keys[] = $this->parseKey($this->previousKey);
        }

        return $keys;
    }
}
