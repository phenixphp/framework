<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\Compilers\ExistsCompiler;
use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;

class Exists extends ExistsCompiler
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new Where();
    }
}
