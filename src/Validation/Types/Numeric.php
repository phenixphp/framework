<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\In;
use Phenix\Validation\Rules\NotIn;
use Phenix\Validation\Rules\Numbers\Digits;
use Phenix\Validation\Rules\Numbers\DigitsBetween;
use Phenix\Validation\Rules\Numbers\IsNumeric;
use Phenix\Validation\Rules\TypeRule;

class Numeric extends Scalar
{
    protected function defineType(): TypeRule
    {
        return IsNumeric::new();
    }

    public function digits(int $value): self
    {
        $this->rules['digits'] = Digits::new($value);

        return $this;
    }

    public function digitsBetween(int $min, int $max): self
    {
        $this->rules['digits_between'] = DigitsBetween::new($min, $max);

        return $this;
    }

    public function in(array $values): self
    {
        $this->rules['in'] = In::new(array_values($values));

        return $this;
    }

    public function notIn(array $values): self
    {
        $this->rules['not_in'] = NotIn::new(array_values($values));

        return $this;
    }
}
