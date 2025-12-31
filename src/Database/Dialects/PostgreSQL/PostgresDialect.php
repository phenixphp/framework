<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL;

use Phenix\Database\Dialects\Dialect;
use Phenix\Database\Dialects\PostgreSQL\Compilers\Delete;
use Phenix\Database\Dialects\PostgreSQL\Compilers\Exists;
use Phenix\Database\Dialects\PostgreSQL\Compilers\Insert;
use Phenix\Database\Dialects\PostgreSQL\Compilers\Select;
use Phenix\Database\Dialects\PostgreSQL\Compilers\Update;

class PostgresDialect extends Dialect
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
