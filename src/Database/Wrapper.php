<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\Driver;
use Phenix\Database\Constants\SQL;
use Phenix\Database\Contracts\RawValue;

class Wrapper implements RawValue
{
    private function __construct(
        protected readonly string $value,
        protected readonly string $encloser
    ) {
        // ..
    }

    public function __toString(): string
    {
        if (empty($this->value) || $this->value === '*' || $this->value === SQL::PLACEHOLDER->value) {
            return $this->value;
        }

        return $this->encloser
            . str_replace($this->encloser, $this->encloser . $this->encloser, $this->value)
            . $this->encloser;
    }

    public static function doubleQuote(string $value): self
    {
        return new self($value, '"');
    }

    public static function backtick(string $value): self
    {
        return new self($value, '`');
    }

    public static function of(Driver $driver, string $value): self
    {
        return match ($driver) {
            Driver::MYSQL => self::backtick($value),
            Driver::POSTGRESQL, Driver::SQLITE => self::doubleQuote($value),
            default => self::backtick($value),
        };
    }

    public static function column(Driver $driver, string $column): string
    {
        $parts = array_map(
            fn (string $part): string => (string) self::of($driver, $part),
            explode('.', $column)
        );

        return implode('.', $parts);
    }

    public static function columnList(Driver $driver, array $columns): array
    {
        return array_map(fn (string $column): string => self::column($driver, $column), $columns);
    }
}
