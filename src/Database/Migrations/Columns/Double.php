<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Double extends Number
{
    public function __construct(
        protected string $name,
        protected bool $signed = true
    ) {
        parent::__construct($name);
        $this->options['signed'] = $signed;
    }

    public function getType(): string
    {
        return 'double';
    }

    public function default(float|int $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }

    public function unsigned(): static
    {
        if ($this->isMysql()) {
            $this->options['signed'] = false;
        }

        return $this;
    }

    public function signed(): static
    {
        if ($this->isMysql()) {
            $this->options['signed'] = true;
        }

        return $this;
    }
}
