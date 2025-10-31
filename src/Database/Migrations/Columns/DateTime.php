<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class DateTime extends Column
{
    public function getType(): string
    {
        return 'datetime';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }
}
