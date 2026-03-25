<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use function array_is_list;
use function is_array;

class IsList extends TypeRule
{
    public function passes(): bool
    {
        $value = $this->getValue();

        return is_array($value)
            && array_is_list($value)
            && $this->isScalar($value);
    }

    protected function isScalar(array $data): bool
    {
        foreach ($data as $item) {
            if (! is_scalar($item)) {
                return false;
            }
        }

        return true;
    }

    public function message(): string|null
    {
        return trans('validation.list', ['field' => $this->getFieldForHumans()]);
    }
}
