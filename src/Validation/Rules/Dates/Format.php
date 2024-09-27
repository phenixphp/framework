<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules\Dates;

use DateTime;
use Phenix\Validation\Rules\Rule;

class Format extends Rule
{
    public function __construct(
        protected string $format
    ) {
    }

    public function passes(): bool
    {
        $dateTime = DateTime::createFromFormat($this->format, $this->getValue());

        return $dateTime instanceof DateTime;
    }
}
