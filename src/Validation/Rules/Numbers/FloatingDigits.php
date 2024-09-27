<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Numbers;

use function explode;

class FloatingDigits extends Digits
{
    public function passes(): bool
    {
        $number = (string) $this->getValue();

        [$digits,] = explode('.', $number);

        return strlen($digits) === $this->digits;
    }
}
