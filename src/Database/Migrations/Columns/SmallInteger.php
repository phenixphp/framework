<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class SmallInteger extends Column
{
    public function __construct(
        protected string $name,
        bool $identity = false,
        bool $signed = true
    ) {
        parent::__construct($name);
        
        if ($identity) {
            $this->options['identity'] = true;
        }

        if (! $signed) {
            $this->options['signed'] = false;
        }
    }

    public function getType(): string
    {
        return 'smallinteger';
    }

    public function default(int $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function identity(): static
    {
        $this->options['identity'] = true;
        $this->options['null'] = false;

        return $this;
    }

    public function unsigned(): static
    {
        $this->options['signed'] = false;

        return $this;
    }

    public function signed(): static
    {
        $this->options['signed'] = true;

        return $this;
    }
}
