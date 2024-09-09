<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Numbers;

use Phenix\Validation\Rules\Between;

class DigitsBetween extends Between
{
    public function passes(): bool
    {
        $value = $this->getValue();

        $digits = match ($this->getValueType()) {
            'integer', 'double' => strlen((string) $value),
            default => $value,
        };

        return $digits >= $this->min && $digits <= $this->max;
    }
}
