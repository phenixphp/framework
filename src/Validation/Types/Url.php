<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\IsUrl;
use Phenix\Validation\Rules\TypeRule;

class Url extends Str
{
    protected function defineType(): TypeRule
    {
        return IsUrl::new();
    }
}
