<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Char extends Column
{
    public function __construct(
        protected string $name,
        protected int $limit = 255
    ) {
        parent::__construct($name);
        $this->options['limit'] = $limit;
    }

    public function getType(): string
    {
        return 'char';
    }

    public function limit(int $limit): static
    {
        $this->options['limit'] = $limit;

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

    public function default(string $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
