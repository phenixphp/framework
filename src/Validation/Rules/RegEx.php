<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class RegEx extends Rule
{
    public function __construct(
        protected string $regEx
    ) {
    }

    public function passes(): bool
    {
        return preg_match($this->regEx, $this->getValue()) > 0;
    }
}
