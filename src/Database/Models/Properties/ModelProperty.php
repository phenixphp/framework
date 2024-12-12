<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

use Phenix\Database\Models\Attributes\Column;
use Phenix\Util\Date;

use function is_null;

class ModelProperty implements ModelPropertyInterface
{
    public function __construct(
        protected string $name,
        protected string $type,
        protected bool $isInstantiable,
        protected Column $attribute,
        protected mixed $value,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumnName(): string
    {
        return $this->attribute->getColumnName() ?? $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isInstantiable(): bool
    {
        return $this->isInstantiable;
    }

    public function getAttribute(): Column
    {
        return $this->attribute;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    protected function resolveDate(mixed $value): object|null
    {
        if (is_null($value) && $this->isNullable()) {
            return null;
        }

        return Date::parse($value);
    }

    protected function resolveType(mixed $value): object|null
    {
        if (is_null($value) && $this->isNullable()) {
            return null;
        }

        return new $this->type($value);
    }
}
