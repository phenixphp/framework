<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Json extends Column
{
    public function getType(): string
    {
        return 'json';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }
}
