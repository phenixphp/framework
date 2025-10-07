<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Util\Date;

class Before extends After
{
    public function passes(): bool
    {
        return Date::parse($this->getValue())->lessThan($this->date);
    }

    public function message(): string|null
    {
        return trans('validation.date.before', ['field' => $this->getFieldForHumans()]);
    }
}
