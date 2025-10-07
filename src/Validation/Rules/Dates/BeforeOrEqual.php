<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class BeforeOrEqual extends Before
{
    public function passes(): bool
    {
        return Date::parse($this->getValue())->lessThanOrEqualTo($this->date);
    }

    public function message(): string|null
    {
        return trans('validation.date.before_or_equal', ['field' => $this->getFieldForHumans()]);
    }
}
