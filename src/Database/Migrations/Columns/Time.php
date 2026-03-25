<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Time extends Column
{
    public function __construct(
        protected string $name,
        protected bool $timezone = false
    ) {
        parent::__construct($name);

        if ($timezone && $this->isPostgres()) {
            $this->options['timezone'] = true;
        }
    }

    public function getType(): string
    {
        return 'time';
    }

    public function withTimezone(bool $timezone = true): static
    {
        if ($this->isPostgres()) {
            $this->options['timezone'] = $timezone;
        }

        return $this;
    }

    public function default(string $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
