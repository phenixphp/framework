<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phenix\Database\Migrations\Columns\Concerns\HasSign;

class UnsignedBigInteger extends Number
{
    use HasSign;

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
        return 'biginteger';
    }
}
