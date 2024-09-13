<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function count;
use function is_countable;
use function is_object;
use function is_scalar;
use function is_string;
use function trim;

class Required extends Requirement
{
    public function passes(): bool
    {
        $value = $this->getValue();

        if ($value === null) {
            return false;
        }

        return (is_string($value) && ! empty(trim($value)))
            || (is_countable($value) && count($value) > 0)
            || (is_scalar($value) || is_object($value));
    }

    public function skip(): bool
    {
        return false;
    }
}
