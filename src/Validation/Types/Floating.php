<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\Between;
use Phenix\Validation\Rules\In;
use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\NotIn;
use Phenix\Validation\Rules\Numbers\DecimalDigits;
use Phenix\Validation\Rules\Numbers\DecimalDigitsBetween;
use Phenix\Validation\Rules\Numbers\FloatingDigits;
use Phenix\Validation\Rules\Numbers\FloatingDigitsBetween;
use Phenix\Validation\Rules\Numbers\IsFloat;
use Phenix\Validation\Rules\TypeRule;

class Floating extends Scalar
{
    protected function defineType(): TypeRule
    {
        return IsFloat::new();
    }

    public function min(float $limit)
    {
        $this->rules['min'] = Min::new($limit);

        return $this;
    }

    public function max(float $limit): self
    {
        $this->rules['max'] = Max::new($limit);

        return $this;
    }

    public function between(float $min, float $max): self
    {
        $this->rules['between'] = Between::new($min, $max);

        return $this;
    }

    public function digits(int $digits): self
    {
        $this->rules['digits'] = FloatingDigits::new($digits);

        return $this;
    }

    public function digitsBetween(int $min, int $max): self
    {
        $this->rules['digits_between'] = FloatingDigitsBetween::new($min, $max);

        return $this;
    }

    public function decimals(int $decimals): self
    {
        $this->rules['decimals'] = DecimalDigits::new($decimals);

        return $this;
    }

    public function decimalsBetween(int $digits, int|null $decimals = null): self
    {
        $this->rules['decimals_between'] = DecimalDigitsBetween::new($digits, $decimals);

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
