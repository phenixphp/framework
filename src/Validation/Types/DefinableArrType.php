<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Util\Arr;

abstract class DefinableArrType extends ArrType
{
    public function define(array $definition): static
    {
        if (! $this->isValidDefinition($definition)) {
            $this->throwsDefinitionError();
        }

        $this->definition = $definition;

        return $this;
    }

    protected function isValidDefinition(array $definition): bool
    {
        return ! array_is_list($definition)
            && Arr::every($definition, fn ($value, $key) => is_string($key) && $value instanceof Type);
    }

    abstract protected function throwsDefinitionError(): never;
}
