<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function is_null;

class Nullable extends Required
{
    public function passes(): bool
    {
        if (! $this->data->has($this->field)) {
            return false;
        }

        if (is_null($this->getValue())) {
            return true;
        }

        return parent::passes();
    }

    public function skip(): bool
    {
        return is_null($this->getValue());
    }
}
