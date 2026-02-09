<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Mysql;

use Phenix\Database\Dialects\Dialect;
use Phenix\Database\Dialects\Mysql\Compilers\Delete;
use Phenix\Database\Dialects\Mysql\Compilers\Exists;
use Phenix\Database\Dialects\Mysql\Compilers\Insert;
use Phenix\Database\Dialects\Mysql\Compilers\Select;
use Phenix\Database\Dialects\Mysql\Compilers\Update;

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
