<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql\Compilers;

use Phenix\Database\Dialects\Compilers\DeleteCompiler;

class Delete extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }
}
