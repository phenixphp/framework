<?php

declare(strict_types=1);

namespace Phenix\Crypto\Contracts;

interface Cipher
{
    public function encrypt(object|array|string $value, bool $serialize = true): string;

    public function decrypt(string $payload, bool $unserialize = true): object|array|string;
}
