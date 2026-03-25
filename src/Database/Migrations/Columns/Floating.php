<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Floating extends Column
{
    public function getType(): string
    {
        return 'float';
    }

    public function default(float $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }
}
