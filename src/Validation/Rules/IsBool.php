<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function in_array;

class IsBool extends TypeRule
{
    public function passes(): bool
    {
        return in_array($this->getValue(), [true, false, 'true', 'false', 1, 0, '1', '0'], true);
    }

    public function message(): string|null
    {
    return trans('validation.boolean', ['field' => $this->getFieldForHumans()]);
    }
}
