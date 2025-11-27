<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations\Columns\Internal;

use Phenix\Database\Migrations\Columns\Number;

class TestNumber extends Number
{
    public function getType(): string
    {
        return 'test_number';
    }
}
