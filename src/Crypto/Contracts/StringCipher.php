<?php

declare(strict_types=1);

namespace Phenix\Crypto\Contracts;

interface StringCipher
{
    public function encryptString(string $value): string;

    public function decryptString(string $payload): string;
}
