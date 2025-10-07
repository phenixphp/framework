<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Min extends Size
{
    public function passes(): bool
    {
        return $this->getValue() >= $this->limit;
    }

    public function message(): string|null
    {
        $value = $this->data->get($this->field) ?? null;
        $type = gettype($value);

        $key = match ($type) {
            'string' => 'validation.min.string',
            'array' => 'validation.min.array',
            'object' => 'validation.min.file',
            default => 'validation.min.numeric',
        };

        return trans($key, [
            'field' => $this->getFieldForHumans(),
            'min' => $this->limit,
        ]);
    }
}
