<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Ulid extends Column
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->options['limit'] = 26;
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

    public function limit(int $limit): static
    {
        return $this;
    }

    public function length(int $length): static
    {
        return $this;
    }
}
