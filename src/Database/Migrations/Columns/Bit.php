<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use InvalidArgumentException;

class Bit extends Column
{
    public function __construct(
        protected string $name,
        protected int $limit = 1
    ) {
        parent::__construct($name);
        $this->options['limit'] = $limit;
    }

    public function getType(): string
    {
        return 'bit';
    }

    public function limit(int $limit): static
    {
        if ($limit < 1 || $limit > 64) {
            throw new InvalidArgumentException('Bit limit must be between 1 and 64');
        }

        $this->options['limit'] = $limit;

        return $this;
    }

    public function default(int $default): static
    {
        $this->options['default'] = $default;

        return $this;
    }
}
