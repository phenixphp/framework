<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class UnsignedDecimal extends Column
{
    public function __construct(
        protected string $name,
        int $precision = 10,
        int $scale = 2,
    ) {
        parent::__construct($name);
        $this->options['precision'] = $precision;
        $this->options['scale'] = $scale;
        $this->options['signed'] = false;
    }

    public function getType(): string
    {
        return 'decimal';
    }

    public function default(float $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function precision(int $precision): static
    {
        $this->options['precision'] = $precision;

        return $this;
    }

    public function scale(int $scale): static
    {
        $this->options['scale'] = $scale;

        return $this;
    }
}
