<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class DoesNotEndWith extends EndsWith
{
    public function passes(): bool
    {
        return ! parent::passes();
    }
}
