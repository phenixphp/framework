<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Timestamp extends Column
{
    public function __construct(
        protected string $name,
        bool $timezone = false
    ) {
        if ($timezone) {
            $this->options['timezone'] = true;
        }
    }

    public function getType(): string
    {
        return 'timestamp';
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

    public function timezone(): static
    {
        $this->options['timezone'] = true;

        return $this;
    }

    public function update(string $action): static
    {
        $this->options['update'] = $action;

        return $this;
    }

    public function currentTimestamp(): static
    {
        $this->options['default'] = 'CURRENT_TIMESTAMP';

        return $this;
    }

    public function onUpdateCurrentTimestamp(): static
    {
        $this->options['update'] = 'CURRENT_TIMESTAMP';

        return $this;
    }
}
