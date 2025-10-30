<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Str extends Column
{
    public function __construct(
        protected string $name,
        int $limit = 255
    ) {
        parent::__construct($name);
        $this->options['limit'] = $limit;
    }

    public function getType(): string
    {
        return 'string';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function collation(string $collation): static
    {
        $this->options['collation'] = $collation;

        return $this;
    }

    public function encoding(string $encoding): static
    {
        $this->options['encoding'] = $encoding;

        return $this;
    }
}
