<?php

declare(strict_types=1);

namespace Phenix\Crypto;

use Phenix\Crypto\Contracts\Hasher as HasherContract;
use Phenix\Crypto\Tasks\CheckNeedsRehash;
use Phenix\Crypto\Tasks\GeneratePasswordHash;
use Phenix\Crypto\Tasks\VerifyPasswordHash;
use Phenix\Tasks\Result;
use SensitiveParameter;

class Hash implements HasherContract
{
    public function make(#[SensitiveParameter] string $password): string
    {
        $task = new GeneratePasswordHash($password);

        /** @var Result $result */
        $result = $task->output();

        return $result->output();
    }

    public function verify(string $hash, #[SensitiveParameter] string $password): bool
    {
        $task = new VerifyPasswordHash($hash, $password);

        /** @var Result $result */
        $result = $task->output();

        return $result->output();
    }

    public function needsRehash(string $hash): bool
    {
        $task = new CheckNeedsRehash($hash);

        /** @var Result $result */
        $result = $task->output();

        return $result->output();
    }
}
