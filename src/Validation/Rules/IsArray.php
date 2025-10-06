<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function is_array;

class IsArray extends TypeRule
{
    public function passes(): bool
    {
        return is_array($this->getValue());
    }

    public function message(): string|null
    {
        return trans('validation.array', ['field' => $this->field]);
    }
}
