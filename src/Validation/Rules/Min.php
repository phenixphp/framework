<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Min extends Size
{
    public function passes(): bool
    {
        return $this->getValue() >= $this->limit;
    }
}
