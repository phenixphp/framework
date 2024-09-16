<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function is_string;

class IsString extends TypeRule
{
    public function passes(): bool
    {
        return is_string($this->getValue());
    }
}
