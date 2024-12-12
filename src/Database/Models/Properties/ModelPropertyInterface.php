<?php

declare(strict_types=1);

namespace Phenix\Database\Models\Properties;

interface ModelPropertyInterface
{
    public function resolveInstance(mixed $value = null): object|null;

    public function isNullable(): bool;

    public function getName(): string;

    public function getColumnName(): string;

    public function getType(): string;

    public function isInstantiable(): bool;

    public function getAttribute(): object;

    public function getValue(): mixed;
}
