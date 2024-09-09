<?php

declare(strict_types=1);

namespace Phenix\Validation\Contracts;

use Adbar\Dot;

interface Rule
{
    public function setField(string $field): self;

    public function setData(Dot|array $data): self;

    public function passes(): bool;
}
