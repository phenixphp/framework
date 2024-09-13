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

        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_countable($value) && count($value) < 1) {
            return false;
        } else {
            return true;
        }
    }

    public function skip(): bool
    {
        return false;
    }
}
