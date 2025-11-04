<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Timestamp extends Column
{
    public function __construct(
        protected string $name,
        bool $timezone = false
    ) {
        parent::__construct($name);
        if ($timezone) {
            $this->options['timezone'] = true;
        }
    }

    public function getType(): string
    {
        return 'timestamp';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }

    public function timezone(bool $timezone = true): static
    {
        $this->options['timezone'] = $timezone;

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
