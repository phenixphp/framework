<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function in_array;

class Mimes extends Rule
{
    public function __construct(
        protected array $haystack
    ) {
    }

    public function passes(): bool
    {
        return in_array($this->getValue()->getMimeType(), $this->haystack, true);
    }
}
