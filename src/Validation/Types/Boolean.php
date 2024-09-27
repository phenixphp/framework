<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\IsBool;
use Phenix\Validation\Rules\TypeRule;

class Boolean extends Scalar
{
    protected function defineType(): TypeRule
    {
        return IsBool::new();
    }
}
