<?php

declare(strict_types=1);

namespace Phenix\Views;

use Stringable;

class EscapedValue implements Stringable
{
    public function __construct(
        protected string $value,
    ) {
    }

    public function __toString(): string
    {
        return e($this->value);
    }
}
