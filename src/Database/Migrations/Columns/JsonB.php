<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class JsonB extends Column
{
    public function __construct(
        protected string $name
    ) {
        parent::__construct($name);
    }

    public function getType(): string
    {
        return 'jsonb';
    }

    public function default(string|array $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
