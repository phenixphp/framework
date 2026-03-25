<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class UnsignedFloat extends Column
{
    public function __construct(
        protected string $name
    ) {
        parent::__construct($name);
        $this->options['signed'] = false;
    }

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
