<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite;

use Phenix\Database\Dialects\Dialect;
use Phenix\Database\Dialects\Sqlite\Compilers\Delete;
use Phenix\Database\Dialects\Sqlite\Compilers\Exists;
use Phenix\Database\Dialects\Sqlite\Compilers\Insert;
use Phenix\Database\Dialects\Sqlite\Compilers\Select;
use Phenix\Database\Dialects\Sqlite\Compilers\Update;

class SqliteDialect extends Dialect
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
