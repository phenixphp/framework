<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function array_is_list;
use function is_array;

class IsCollection extends IsList
{
    public function passes(): bool
    {
        $value = $this->getValue();

        return is_array($value)
            && array_is_list($value)
            && ! $this->isScalar($value);
    }
}
