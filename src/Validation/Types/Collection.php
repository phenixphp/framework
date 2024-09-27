<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Exceptions\InvalidCollectionDefinition;
use Phenix\Validation\Rules\IsCollection;
use Phenix\Validation\Rules\TypeRule;

class Collection extends DefinableArrType
{
    protected function defineType(): TypeRule
    {
        return IsCollection::new();
    }

    protected function throwsDefinitionError(): never
    {
        throw new InvalidCollectionDefinition('The collection definition is invalid.');
    }
}
