<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class IsUrl extends IsString
{
    public function passes(): bool
    {
        return parent::passes()
            && filter_var($this->getValue(), FILTER_VALIDATE_URL) !== false;
    }

    public function message(): string|null
    {
        return trans('validation.url', ['field' => $this->getFieldForHumans()]);
    }
}
