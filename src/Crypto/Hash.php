<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Hasher as HasherContract;
use Phenix\Crypto\Tasks\CheckNeedsRehash;
use Phenix\Crypto\Tasks\GeneratePasswordHash;
use Phenix\Crypto\Tasks\VerifyPasswordHash;
use Phenix\Tasks\Result;
use Phenix\Tasks\Worker;
use SensitiveParameter;

class Hash implements HasherContract
{
    public function make(#[SensitiveParameter] string $password): string
    {
        /** @var Result $result */
        [$result] = Worker::batch([
            new GeneratePasswordHash($password),
        ]);

        return $result->output();
    }

    public function verify(string $hash, #[SensitiveParameter] string $password): bool
    {
        /** @var Result $result */
        [$result] = Worker::batch([
            new VerifyPasswordHash($hash, $password),
        ]);

        return $result->output();
    }

    public function needsRehash(string $hash): bool
    {
        /** @var Result $result */
        [$result] = Worker::batch([
            new CheckNeedsRehash($hash),
        ]);

        return $result->output();
    }
}
