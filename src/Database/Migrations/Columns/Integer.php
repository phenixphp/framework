<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Integer extends Column
{
    public function __construct(
        protected string $name,
        ?int $limit = null,
        bool $identity = false,
        bool $signed = true
    ) {
        if ($limit !== null) {
            $this->options['limit'] = $limit;
        }

        if ($identity) {
            $this->options['identity'] = true;
            $this->options['null'] = false;
        }

        if (! $signed) {
            $this->options['signed'] = false;
        }
    }

    public function getType(): string
    {
        return 'integer';
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

    public function default(int $value): static
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
