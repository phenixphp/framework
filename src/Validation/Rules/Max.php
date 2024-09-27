<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Max extends Min
{
    public function passes(): bool
    {
        return $this->getValue() <= $this->limit;
    }
}
