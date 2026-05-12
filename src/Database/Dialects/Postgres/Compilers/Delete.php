<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Postgres\Compilers;

use Phenix\Database\Dialects\Postgres\Concerns\HasPlaceholders;
use Phenix\Database\Dialects\Sqlite\Compilers\Delete as SQLiteDelete;

class Delete extends SQLiteDelete
{
    use HasPlaceholders;

    public function __construct()
    {
        $this->whereCompiler = new Where();
    }
}
