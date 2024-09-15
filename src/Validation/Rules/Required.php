<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function count;
use function is_countable;
use function is_string;
use function trim;

class Required extends Requirement
{
    public function passes(): bool
    {
        $value = $this->getValue();

        if (is_null($value)) {
            $passes = false;
        } elseif (is_string($value)) {
            $passes = trim($value) !== '';
        } elseif (is_countable($value)) {
            $passes = count($value) > 0;
        } else {
            $passes = true;
        }

        return $passes;
    }

    public function skip(): bool
    {
        return false;
    }
}
