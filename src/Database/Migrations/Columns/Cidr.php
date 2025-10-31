<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Cidr extends Column
{
    public function __construct(
        protected string $name
    ) {
        parent::__construct($name);
    }

    public function getType(): string
    {
        return 'cidr';
    }

    public function default(string $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
