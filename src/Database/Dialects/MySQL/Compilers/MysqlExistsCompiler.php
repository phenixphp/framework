<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL\Compilers;

use Phenix\Database\Dialects\Compilers\ExistsCompiler;

final class MysqlExistsCompiler extends ExistsCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new MysqlWhereCompiler();
    }
}
