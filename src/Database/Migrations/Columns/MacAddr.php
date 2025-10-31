<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class MacAddr extends Column
{
    public function __construct(
        protected string $name
    ) {
        parent::__construct($name);
    }

    public function getType(): string
    {
        return 'macaddr';
    }

    public function default(string $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
