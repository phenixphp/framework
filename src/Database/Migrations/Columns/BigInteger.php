<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phenix\Database\Migrations\Columns\Concerns\HasSign;

class BigInteger extends UnsignedBigInteger
{
    use HasSign;

    public function __construct(
        protected string $name,
        bool $identity = false,
    ) {
        parent::__construct($name, $identity);

        $this->options['signed'] = true;
    }

    public function getType(): string
    {
        return 'biginteger';
    }
}
