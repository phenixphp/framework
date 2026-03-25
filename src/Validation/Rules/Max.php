<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Max extends Min
{
    public function passes(): bool
    {
        return $this->getValue() <= $this->limit;
    }

    public function message(): string|null
    {
        $value = $this->data->get($this->field) ?? null;
        $type = gettype($value);

        $key = match ($type) {
            'string' => 'validation.max.string',
            'array' => 'validation.max.array',
            'object' => 'validation.max.file',
            default => 'validation.max.numeric',
        };

        return trans($key, [
            'field' => $this->getFieldForHumans(),
            'max' => $this->limit,
        ]);
    }
}
