<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Sqlite\Compilers;

use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Database\Dialects\SqlData;
use Phenix\Util\Arr;

class Delete extends DeleteCompiler
{
    public function __construct()
    {
        $this->whereCompiler = new Where();
    }

    public function compile(): SqlData
    {
        $parts = [];
        $params = [];
        $table = $this->compileTable();

        $parts[] = 'DELETE FROM';
        $parts[] = $table->sql;
        $params = [...$params, ...$table->params];

        if (! empty($this->ast->wheres)) {
            $whereCompiled = $this->whereCompiler->compile($this->ast->wheres);

            $parts[] = 'WHERE';
            $parts[] = $whereCompiled->sql;
            $params = [...$params, ...$whereCompiled->params];
        }

        if (! empty($this->ast->returning)) {
            $parts[] = 'RETURNING';
            $parts[] = Arr::implodeDeeply($this->wrapList($this->ast->returning), ', ');
        }

        $sql = Arr::implodeDeeply($parts);

        return new SqlData($this->replacePlaceholders($sql), $params);
    }
}
