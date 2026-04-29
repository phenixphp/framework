<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Contracts\RawValue;

use function is_int;

class Value implements RawValue
{
    public function __construct(
        protected readonly string|int $value
    ) {
        // ..
    }

    public function __toString(): string
    {
        if (is_int($this->value)) {
            return (string) $this->value;
        }

        return "'" . str_replace("'", "''", $this->value) . "'";
    }

    public static function from(string|int $value): self
    {
        return new self($value);
    }
}
