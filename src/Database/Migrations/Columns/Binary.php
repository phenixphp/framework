<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Binary extends Column
{
    public function __construct(
        protected string $name,
        ?int $limit = null
    ) {
        if ($limit !== null) {
            $this->options['limit'] = $limit;
        }
    }

    public function getType(): string
    {
        return 'binary';
    }

    public function nullable(): static
    {
        $this->options['null'] = true;

        return $this;
    }

    public function notNull(): static
    {
        $this->options['null'] = false;

        return $this;
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function comment(string $comment): static
    {
        $this->options['comment'] = $comment;

        return $this;
    }

    public function after(string $column): static
    {
        $this->options['after'] = $column;

        return $this;
    }
}
