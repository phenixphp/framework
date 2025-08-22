<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Contracts\RawValue;

class Value implements RawValue
{
    public function __construct(
        protected readonly string|int $value
    ) {
        // ..
    }

    public function __toString(): string
    {
        return "'" . $this->value . "'";
    }

    public static function from(string|int $value): self
    {
        return new self($value);
    }
}
