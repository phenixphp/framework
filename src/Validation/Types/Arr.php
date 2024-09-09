<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\IsArray;
use Phenix\Validation\Rules\TypeRule;

class Arr extends ArrType
{
    public function define(array $definition)
    {
        $this->definition = $definition;

        return $this;
    }

    protected function defineType(): TypeRule
    {
        return IsArray::new();
    }
}
