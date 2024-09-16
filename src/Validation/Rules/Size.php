<?php

declare(strict_types=1);

namespace Phenix\Validation\Rules;

use Countable;

use function gettype;

class Size extends Rule
{
    protected float|int $limit;

    public function __construct(float|int $limit)
    {
        $this->limit = abs($limit);
    }

    public function passes(): bool
    {
        return $this->getValue() === $this->limit;
    }

    protected function getValue(): float|int
    {
        $value = $this->data->get($this->field) ?? null;

        return match (gettype($value)) {
            'string' => strlen($value),
            'array' => count($value),
            'integer', 'double' => $value,
            'object' => $this->resolveCountableObject($value),
            default => 0,
        };
    }

    private function resolveCountableObject(object $value): int
    {
        $count = 0;

        if ($value instanceof Countable) {
            $count = $value->count();
        }

        return $count;
    }
}
