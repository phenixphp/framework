<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class Integer extends UnsignedInteger
{
    public function __construct(
        protected string $name,
        int|null $limit = null,
        bool $identity = false,
    ) {
        parent::__construct($name, $limit, $identity);

        $this->options['signed'] = true;
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
