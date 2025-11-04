<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

class UnsignedInteger extends Number
{
    public function __construct(
        protected string $name,
        int|null $limit = null,
        bool $identity = false,
    ) {
        parent::__construct($name);

        $this->options['signed'] = false;

        if ($limit) {
            $this->options['limit'] = $limit;
        }

        if ($identity) {
            $this->options['identity'] = true;
        }
    }

    public function getType(): string
    {
        return 'integer';
    }
}
