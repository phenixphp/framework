<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class NotIn extends In
{
    public function passes(): bool
    {
        return ! parent::passes();
    }

    public function message(): string|null
    {
        return trans('validation.not_in', [
            'field' => $this->getFieldForHumans(),
            'values' => implode(', ', $this->haystack),
        ]);
    }
}
