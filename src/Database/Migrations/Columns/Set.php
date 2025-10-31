<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Set extends Column
{
    public function __construct(
        protected string $name,
        protected array $values
    ) {
        parent::__construct($name);
        $this->options['values'] = $values;
    }

    public function getType(): string
    {
        return 'set';
    }

    public function values(array $values): static
    {
        $this->options['values'] = $values;

        return $this;
    }

    public function default(string|array $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }

    public function collation(string $collation): static
    {
        if ($this->isMysql()) {
            $this->options['collation'] = $collation;
        }

        return $this;
    }

    public function encoding(string $encoding): static
    {
        if ($this->isMysql()) {
            $this->options['encoding'] = $encoding;
        }

        return $this;
    }
}
