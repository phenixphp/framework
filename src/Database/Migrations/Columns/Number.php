<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

abstract class Number extends Column
{
    public function default(int $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function identity(): static
    {
        $this->options['identity'] = true;

        return $this;
    }
}
