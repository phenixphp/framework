<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phenix\Database\Migrations\Columns\Concerns\HasSign;

class Integer extends UnsignedInteger
{
    use HasSign;

    public function __construct(
        protected string $name,
        int|null $limit = null,
        bool $identity = false,
    ) {
        parent::__construct($name, $limit, $identity);

        $this->options['signed'] = true;
    }
}
