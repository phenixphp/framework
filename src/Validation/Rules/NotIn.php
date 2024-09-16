<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class NotIn extends In
{
    public function passes(): bool
    {
        return ! parent::passes();
    }
}
