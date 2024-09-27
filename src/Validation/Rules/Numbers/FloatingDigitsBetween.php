<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Numbers;

use Phenix\Validation\Rules\Between;

class FloatingDigitsBetween extends Between
{
    public function passes(): bool
    {
        $number = (string) $this->getValue();

        [$digits,] = explode('.', $number);

        return strlen($digits) >= $this->min && strlen($digits) <= $this->max;
    }
}
