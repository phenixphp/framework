<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use Phenix\Validation\Util\Date;

class AfterOrEqual extends After
{
    public function passes(): bool
    {
        return Date::parse($this->getValue())->greaterThanOrEqualTo($this->date);
    }
}
