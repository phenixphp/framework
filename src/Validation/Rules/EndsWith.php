<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class EndsWith extends StartsWith
{
    public function passes(): bool
    {
        return str_ends_with($this->getValue(), $this->needle);
    }
}
