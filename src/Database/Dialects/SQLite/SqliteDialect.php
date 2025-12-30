<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\SQLite;

use Phenix\Database\Constants\Action;
use Phenix\Database\Contracts\Dialect;
use Phenix\Database\Dialects\SQLite\Compilers\SqliteDeleteCompiler;
use Phenix\Database\Dialects\SQLite\Compilers\SqliteExistsCompiler;
use Phenix\Database\Dialects\SQLite\Compilers\SqliteInsertCompiler;
use Phenix\Database\Dialects\SQLite\Compilers\SqliteSelectCompiler;
use Phenix\Database\Dialects\SQLite\Compilers\SqliteUpdateCompiler;
use Phenix\Database\QueryAst;

final class SqliteDialect implements Dialect
{
    private SqliteSelectCompiler $selectCompiler;
    private SqliteInsertCompiler $insertCompiler;
    private SqliteUpdateCompiler $updateCompiler;
    private SqliteDeleteCompiler $deleteCompiler;
    private SqliteExistsCompiler $existsCompiler;

    public function __construct()
    {
        $this->selectCompiler = new SqliteSelectCompiler();
        $this->insertCompiler = new SqliteInsertCompiler();
        $this->updateCompiler = new SqliteUpdateCompiler();
        $this->deleteCompiler = new SqliteDeleteCompiler();
        $this->existsCompiler = new SqliteExistsCompiler();
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
