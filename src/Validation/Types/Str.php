<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\DoesNotEndWith;
use Phenix\Validation\Rules\DoesNotStartWith;
use Phenix\Validation\Rules\EndsWith;
use Phenix\Validation\Rules\In;
use Phenix\Validation\Rules\IsString;
use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\NotIn;
use Phenix\Validation\Rules\RegEx;
use Phenix\Validation\Rules\Size;
use Phenix\Validation\Rules\StartsWith;
use Phenix\Validation\Rules\TypeRule;

class Str extends QueryableScalar
{
    protected function defineType(): TypeRule
    {
        return IsString::new();
    }

    public function min(int $limit): self
    {
        $this->rules['min'] = Min::new($limit);

        return $this;
    }

    public function max(int $limit): self
    {
        $this->rules['max'] = Max::new($limit);

        return $this;
    }

    public function size(int $limit): self
    {
        $this->rules['size'] = Size::new($limit);

        return $this;
    }

    public function regex(string $regex): self
    {
        $this->rules['regex'] = RegEx::new($regex);

        return $this;
    }

    public function matchAlpha(): self
    {
        $this->regex('/^[a-zA-Z]+$/');

        return $this;
    }

    public function matchAlphaNum(): self
    {
        $this->regex('/^[a-zA-Z0-9]+$/');

        return $this;
    }

    public function matchAlphaDash(): self
    {
        $this->regex('/^[a-zA-Z_-]+$/');

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

    public function startsWidth(string $needle): self
    {
        $this->rules['starts_with'] = StartsWith::new($needle);

        return $this;
    }

    public function doesNotStartWidth(string $needle): self
    {
        $this->rules['does_not_start_with'] = DoesNotStartWith::new($needle);

        return $this;
    }

    public function endsWidth(string $needle): self
    {
        $this->rules['ends_with'] = EndsWith::new($needle);

        return $this;
    }

    public function doesNotEndWidth(string $needle): self
    {
        $this->rules['does_not_end_with'] = DoesNotEndWith::new($needle);

        return $this;
    }
}
