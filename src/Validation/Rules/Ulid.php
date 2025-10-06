<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Phenix\Util\Str;

class Ulid extends Rule
{
    public function passes(): bool
    {
        return Str::isUlid($this->getValue());

    }

    public function message(): string|null
    {
        return trans('validation.ulid', ['field' => $this->field]);
    }
}
