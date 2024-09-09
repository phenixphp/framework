<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Numbers;

use Phenix\Validation\Rules\TypeRule;

use function is_numeric;

class IsNumeric extends TypeRule
{
    public function passes(): bool
    {
        return is_numeric($this->getValue());
    }
}
