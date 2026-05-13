<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Dialects\Compilers\UpdateCompiler;

class Update extends UpdateCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }
}
