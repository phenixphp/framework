<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

class Between extends Size
{
    protected float|int $min;
    protected float|int $max;

    public function __construct(float|int $min, float|int $max)
    {
        $this->min = abs($min);
        $this->max = abs($max);
    }

    public function passes(): bool
    {
        $value = $this->getValue();

        return $value >= $this->min && $value <= $this->max;
    }

    public function message(): string|null
    {
        $value = $this->data->get($this->field) ?? null;
        $type = gettype($value);

        $key = match ($type) {
            'string' => 'validation.between.string',
            'array' => 'validation.between.array',
            'object' => 'validation.between.file',
            default => 'validation.between.numeric',
        };

        return trans($key, [
            'field' => $this->field,
            'min' => $this->min,
            'max' => $this->max,
        ]);
    }
}
