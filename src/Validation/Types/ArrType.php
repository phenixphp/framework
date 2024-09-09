<?php

declare(strict_types=1);

namespace Phenix\Validation\Types;

use Phenix\Validation\Rules\Max;
use Phenix\Validation\Rules\Min;
use Phenix\Validation\Rules\Size;

abstract class ArrType extends Type
{
    protected Scalar|array $definition;

    public function min(int $limit): static
    {
        $this->rules['min'] = Min::new($limit);

        return $this;
    }

    public function max(int $limit): static
    {
        $this->rules['max'] = Max::new($limit);

        return $this;
    }

    public function size(int $limit): static
    {
        $this->rules['size'] = Size::new($limit);

        return $this;
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            ...['definition' => $this->definition],
        ];
    }
}
