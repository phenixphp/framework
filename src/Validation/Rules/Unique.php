<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Unique extends Exists
{
    public function passes(): bool
    {
        return $this->queryBuilder
            ->whereEqual($this->column ?? $this->field, $this->getValue())
            ->count() === 1;
    }
}
