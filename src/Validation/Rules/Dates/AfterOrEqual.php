<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class AfterOrEqual extends After
{
    public function passes(): bool
    {
        return Date::parse($this->getValue())->greaterThanOrEqualTo($this->date);
    }

    public function message(): string|null
    {
        return trans('validation.date.after_or_equal', ['field' => $this->field]);
    }
}
