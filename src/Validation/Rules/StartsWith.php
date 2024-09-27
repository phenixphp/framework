<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class StartsWith extends Rule
{
    public function __construct(
        protected string $needle
    ) {
    }

    public function passes(): bool
    {
        return str_starts_with($this->getValue(), $this->needle);
    }
}
