<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite;

use Phenix\Database\Constants\Action;
use Phenix\Database\Contracts\Dialect;
use Phenix\Database\Dialects\SQLite\Compilers\Delete;
use Phenix\Database\Dialects\SQLite\Compilers\Exists;
use Phenix\Database\Dialects\SQLite\Compilers\Insert;
use Phenix\Database\Dialects\SQLite\Compilers\Select;
use Phenix\Database\Dialects\SQLite\Compilers\Update;
use Phenix\Database\QueryAst;

class SqliteDialect implements Dialect
{
    private Select $selectCompiler;
    private Insert $insertCompiler;
    private Update $updateCompiler;
    private Delete $deleteCompiler;
    private Exists $existsCompiler;

    public function __construct()
    {
        $this->selectCompiler = new Select();
        $this->insertCompiler = new Insert();
        $this->updateCompiler = new Update();
        $this->deleteCompiler = new Delete();
        $this->existsCompiler = new Exists();
    }

    public function compile(QueryAst $ast): array
    {
        return match ($ast->action) {
            Action::SELECT => $this->compileSelect($ast),
            Action::INSERT => $this->compileInsert($ast),
            Action::UPDATE => $this->compileUpdate($ast),
            Action::DELETE => $this->compileDelete($ast),
            Action::EXISTS => $this->compileExists($ast),
        };
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileSelect(QueryAst $ast): array
    {
        $compiled = $this->selectCompiler->compile($ast);

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileInsert(QueryAst $ast): array
    {
        $compiled = $this->insertCompiler->compile($ast);

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileUpdate(QueryAst $ast): array
    {
        $compiled = $this->updateCompiler->compile($ast);

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileDelete(QueryAst $ast): array
    {
        $compiled = $this->deleteCompiler->compile($ast);

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileExists(QueryAst $ast): array
    {
        $compiled = $this->existsCompiler->compile($ast);

        return [$compiled->sql, $compiled->params];
    }
}
