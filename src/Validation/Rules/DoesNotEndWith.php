<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class DoesNotEndWith extends EndsWith
{
    public function passes(): bool
    {
        return ! parent::passes();
    }

    public function message(): string|null
    {
        return trans('validation.does_not_end_with', [
            'field' => $this->getFieldForHumans(),
            'values' => $this->needle,
        ]);
    }
}
