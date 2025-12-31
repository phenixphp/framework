<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL;

use Phenix\Database\Dialects\Dialect;
use Phenix\Database\Dialects\MySQL\Compilers\Delete;
use Phenix\Database\Dialects\MySQL\Compilers\Exists;
use Phenix\Database\Dialects\MySQL\Compilers\Insert;
use Phenix\Database\Dialects\MySQL\Compilers\Select;
use Phenix\Database\Dialects\MySQL\Compilers\Update;

class MysqlDialect extends Dialect
{
    public function __construct()
    {
        $this->initializeCompilers();
    }

    protected function initializeCompilers(): void
    {
        $this->selectCompiler = new Select();
        $this->insertCompiler = new Insert();
        $this->updateCompiler = new Update();
        $this->deleteCompiler = new Delete();
        $this->existsCompiler = new Exists();
    }
}
