<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Boolean extends Column
{
    public function __construct(
        protected string $name,
        bool $signed = true
    ) {
        if (! $signed) {
            $this->options['signed'] = false;
        }
    }

    public function getType(): string
    {
        return 'boolean';
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

    public function default(bool $value): static
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
