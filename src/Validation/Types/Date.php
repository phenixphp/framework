<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use DateTimeInterface;
use Phenix\Util\Date as Dates;
use Phenix\Validation\Rules\Dates\After;
use Phenix\Validation\Rules\Dates\AfterOrEqual;
use Phenix\Validation\Rules\Dates\AfterOrEqualTo;
use Phenix\Validation\Rules\Dates\AfterTo;
use Phenix\Validation\Rules\Dates\Before;
use Phenix\Validation\Rules\Dates\BeforeOrEqual;
use Phenix\Validation\Rules\Dates\BeforeOrEqualTo;
use Phenix\Validation\Rules\Dates\BeforeTo;
use Phenix\Validation\Rules\Dates\Equal;
use Phenix\Validation\Rules\Dates\EqualTo;
use Phenix\Validation\Rules\Dates\Format;
use Phenix\Validation\Rules\Dates\IsDate;
use Phenix\Validation\Rules\TypeRule;

class Date extends Str
{
    protected function defineType(): TypeRule
    {
        return IsDate::new();
    }

    public function equal(DateTimeInterface|string $date): self
    {
        $this->rules['equal'] = Equal::new($date);

        return $this;
    }

    public function after(DateTimeInterface|string $date): self
    {
        $this->rules['after'] = After::new($date);

        return $this;
    }

    public function afterOrEqual(DateTimeInterface|string $date): self
    {
        $this->rules['after_or_equal'] = AfterOrEqual::new($date);

        return $this;
    }

    public function before(DateTimeInterface|string $date): self
    {
        $this->rules['before'] = Before::new($date);

        return $this;
    }

    public function beforeOrEqual(DateTimeInterface|string $date): self
    {
        $this->rules['before_or_equal'] = BeforeOrEqual::new($date);

        return $this;
    }

    public function format(string $format): self
    {
        $this->rules['date_format'] = Format::new($format);

        return $this;
    }

    public function equalToday(): self
    {
        $this->rules['equal_today'] = Equal::new(Dates::today());

        return $this;
    }

    public function afterToday(): self
    {
        $this->rules['after'] = After::new(Dates::today());

        return $this;
    }

    public function beforeToday(): self
    {
        $this->rules['before'] = Before::new(Dates::today());

        return $this;
    }

    public function afterOrEqualToday(): self
    {
        $this->rules['after_or_equal_today'] = AfterOrEqual::new(Dates::today());

        return $this;
    }

    public function beforeOrEqualToday(): self
    {
        $this->rules['before_or_equal_today'] = BeforeOrEqual::new(Dates::today());

        return $this;
    }

    public function equalTo(string $field): self
    {
        $this->rules['equal_to'] = EqualTo::new($field);

        return $this;
    }

    public function afterTo(string $field): self
    {
        $this->rules['after_to'] = AfterTo::new($field);

        return $this;
    }

    public function beforeTo(string $field): self
    {
        $this->rules['before_to'] = BeforeTo::new($field);

        return $this;
    }

    public function afterOrEqualTo(string $field): self
    {
        $this->rules['after_or_equal_to'] = AfterOrEqualTo::new($field);

        return $this;
    }

    public function beforeOrEqualTo(string $field): self
    {
        $this->rules['before_or_equal_to'] = BeforeOrEqualTo::new($field);

        return $this;
    }
}
