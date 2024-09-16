<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Numbers;

use Phenix\Validation\Rules\TypeRule;

use function is_integer;

class IsInteger extends TypeRule
{
    public function passes(): bool
    {
        return is_integer($this->getValue());
    }
}
