<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite;

use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Database\Dialects\Compilers\ExistsCompiler;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\Dialects\Dialect;
use Phenix\Database\Dialects\Sqlite\Compilers\Delete;
use Phenix\Database\Dialects\Sqlite\Compilers\Exists;
use Phenix\Database\Dialects\Sqlite\Compilers\Insert;
use Phenix\Database\Dialects\Sqlite\Compilers\Select;
use Phenix\Database\Dialects\Sqlite\Compilers\Update;

class SqliteDialect extends Dialect
{
    protected function getSelectCompiler(): SelectCompiler
    {
        return new Select();
    }

    protected function getInsertCompiler(): InsertCompiler
    {
        return new Insert();
    }

    protected function getUpdateCompiler(): UpdateCompiler
    {
        return new Update();
    }

    protected function getDeleteCompiler(): DeleteCompiler
    {
        return new Delete();
    }

    protected function getExistsCompiler(): ExistsCompiler
    {
        return new Exists();
    }
}
