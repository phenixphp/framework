<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\Between;
use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\Numbers\IsInteger;
use Phenix\Validation\Rules\TypeRule;

class Integer extends Numeric
{
    protected function defineType(): TypeRule
    {
        return IsInteger::new();
    }

    public function min(int $limit)
    {
        $this->rules['min'] = Min::new($limit);

        return $this;
    }

    public function max(int $limit): self
    {
        $this->rules['max'] = Max::new($limit);

        return $this;
    }

    public function between(int $min, int $max): self
    {
        $this->rules['between'] = Between::new($min, $max);

        return $this;
    }
}
