<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class DoesNotStartWith extends StartsWith
{
    public function passes(): bool
    {
        return ! parent::passes();
    }

    public function message(): string|null
    {
        return trans('validation.does_not_start_with', [
            'field' => $this->field,
            'values' => $this->needle,
        ]);
    }
}
