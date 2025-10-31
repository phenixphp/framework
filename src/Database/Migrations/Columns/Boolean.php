<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Boolean extends Column
{
    public function getType(): string
    {
        return 'boolean';
    }

    public function default(bool $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }
}
