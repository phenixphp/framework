<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Cipher as CipherContract;
use Phenix\Crypto\Contracts\StringCipher;
use Phenix\Crypto\Exceptions\DecryptException;
use Phenix\Crypto\Exceptions\EncryptException;
use Phenix\Crypto\Tasks\Decrypt;
use Phenix\Crypto\Tasks\Encrypt;
use Phenix\Tasks\Worker;
use SensitiveParameter;

class Crypto implements CipherContract, StringCipher
{
    public function __construct(
        protected string $key,
        protected string|null $previousKey = null
    ) {
    }

    public function encrypt(#[SensitiveParameter] object|array|string $value, bool $serialize = true): string
    {
        [$output] = Worker::batch([
            new Encrypt(
                key: $this->key,
                value: $value,
                serialize: $serialize
            ),
        ]);

        if ($output->isFailure()) {
            throw new EncryptException($output->message());
        }

        return $output->output();
    }

    public function encryptString(#[SensitiveParameter] string $value): string
    {
        return $this->encrypt($value, false);
    }

    public function decrypt(string $payload, bool $unserialize = true): object|array|string
    {
        [$output] = Worker::batch([
            new Decrypt(
                key: $this->key,
                value: $payload,
                unserialize: $unserialize
            ),
        ]);

        if ($output->isFailure()) {
            throw new DecryptException($output->message());
        }

        return $output->output();
    }

    public function decryptString(string $payload): string
    {
        return $this->decrypt($payload, false);
    }
}
