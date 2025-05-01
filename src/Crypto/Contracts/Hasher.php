<?php

declare(strict_types=1);

namespace Phenix\Crypto\Contracts;

interface Hasher
{
    public function make(string $value): string;

    public function verify(string $hash, string $value): bool;

    public function needsRehash(string $hash): bool;
}
