<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Phenix\Util\Str;

class Uuid extends Rule
{
    public function passes(): bool
    {
        return Str::isUuid($this->getValue());
    }
}
