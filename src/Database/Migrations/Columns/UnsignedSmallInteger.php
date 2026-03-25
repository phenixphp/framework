<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class UnsignedSmallInteger extends Number
{
    public function __construct(
        protected string $name,
        bool $identity = false,
    ) {
        parent::__construct($name);
        $this->options['signed'] = false;

        if ($identity) {
            $this->options['identity'] = true;
        }
    }

    public function getType(): string
    {
        return 'smallinteger';
    }

    public function identity(): static
    {
        $this->options['identity'] = true;
        $this->options['null'] = false;

        return $this;
    }
}
