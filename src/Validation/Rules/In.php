<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function in_array;

class In extends Rule
{
    public function __construct(
        protected array $haystack
    ) {
    }

    public function passes(): bool
    {
        return in_array($this->getValue(), $this->haystack, true);
    }
}
