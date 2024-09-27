<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\IsList;
use Phenix\Validation\Rules\TypeRule;

class ArrList extends ArrType
{
    public function define(Scalar $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    protected function defineType(): TypeRule
    {
        return IsList::new();
    }
}
