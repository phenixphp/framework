<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Optional extends Required
{
    public function passes(): bool
    {
        if (! $this->data->has($this->field)) {
            return true;
        }

        return parent::passes();
    }

    public function skip(): bool
    {
        return ! $this->data->has($this->field);
    }
}
