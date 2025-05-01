<?php

declare(strict_types=1);

namespace Phenix\Tasks\Contracts;

interface Result
{
    public function output(): mixed;

    public function message(): string|null;

    public function isSuccess(): bool;

    public function isFailure(): bool;
}
