<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Exceptions\InvalidDictionaryDefinition;
use Phenix\Validation\Rules\IsDictionary;
use Phenix\Validation\Rules\TypeRule;
use Phenix\Validation\Util\Arr;

class Dictionary extends DefinableArrType
{
    protected function defineType(): TypeRule
    {
        return IsDictionary::new();
    }

    protected function throwsDefinitionError(): never
    {
        throw new InvalidDictionaryDefinition('The dictionary definition is invalid.');
    }

    protected function isValidDefinition(array $definition): bool
    {
        return ! array_is_list($definition)
            && Arr::every($definition, fn ($value, $key) => is_string($key) && $value instanceof Scalar);
    }
}
