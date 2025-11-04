<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Enum extends Column
{
    public function __construct(
        protected string $name,
        array $values
    ) {
        parent::__construct($name);
        $this->options['values'] = $values;
    }

    public function getType(): string
    {
        return 'enum';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function values(array $values): static
    {
        $this->options['values'] = $values;

        return $this;
    }
}
