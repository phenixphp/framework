<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Binary extends Column
{
    public function __construct(
        protected string $name,
        int|null $limit = null
    ) {
        parent::__construct($name);

        if ($limit) {
            $this->options['limit'] = $limit;
        }
    }

    public function getType(): string
    {
        return 'binary';
    }

    public function default(string $value): static
    {
        $this->options['default'] = $value;

        return $this;
    }
}
