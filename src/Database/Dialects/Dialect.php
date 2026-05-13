<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects;

use Phenix\Database\Constants\Action;
use Phenix\Database\Constants\SqlMode;
use Phenix\Database\Contracts\Dialect as DialectContract;
use Phenix\Database\Dialects\Compilers\DeleteCompiler;
use Phenix\Database\Dialects\Compilers\ExistsCompiler;
use Phenix\Database\Dialects\Compilers\InsertCompiler;
use Phenix\Database\Dialects\Compilers\SelectCompiler;
use Phenix\Database\Dialects\Compilers\UpdateCompiler;
use Phenix\Database\QueryAst;

abstract class Dialect implements DialectContract
{
    abstract protected function getSelectCompiler(): SelectCompiler;

    abstract protected function getInsertCompiler(): InsertCompiler;

    abstract protected function getUpdateCompiler(): UpdateCompiler;

    abstract protected function getDeleteCompiler(): DeleteCompiler;

    abstract protected function getExistsCompiler(): ExistsCompiler;

    public function compile(QueryAst $ast, SqlMode $sqlMode = SqlMode::Prepared): array
    {
        $compiler = match ($ast->action) {
            Action::SELECT => $this->getSelectCompiler(),
            Action::INSERT => $this->getInsertCompiler(),
            Action::UPDATE => $this->getUpdateCompiler(),
            Action::DELETE => $this->getDeleteCompiler(),
            Action::EXISTS => $this->getExistsCompiler(),
        };

        $compiled = $compiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }
}
