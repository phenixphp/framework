<?php

declare(strict_types=1);

namespace Phenix\Database\Models;

use Phenix\Contracts\Database\ModelProperty;
use Phenix\Util\Date;

use function is_null;

readonly class DatabaseModelProperty
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $isInstantiable,
        public ModelProperty $attribute,
        public mixed $value
    ) {
    }

    public function resolveInstance(mixed $value = null): object|null
    {
        $value ??= $this->value;

        return match ($this->type) {
            Date::class => $this->resolveDate($value),
            default => $this->resolveType($value),
        };
    }

    public function isNullable(): bool
    {
        return str_starts_with($this->type, '?');
    }

    private function resolveDate(mixed $value): object|null
    {
        if (is_null($value) && $this->isNullable()) {
            return null;
        }

        return Date::parse($value);
    }

    private function resolveType(mixed $value): object|null
    {
        if (is_null($value) && $this->isNullable()) {
            return null;
        }

        return new $this->type($value);
    }
}
