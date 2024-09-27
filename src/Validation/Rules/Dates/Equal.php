<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use DateTimeInterface;
use Phenix\Util\Date;
use Phenix\Validation\Rules\Rule;

class Equal extends Rule
{
    protected Date $date;

    public function __construct(DateTimeInterface|string $date)
    {
        $this->date = Date::parse($date);
    }

    public function passes(): bool
    {
        return Date::parse($this->getValue())->equalTo($this->date);
    }
}
