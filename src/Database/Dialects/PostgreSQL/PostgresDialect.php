<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\PostgreSQL;

use Phenix\Database\Constants\Action;
use Phenix\Database\Contracts\Dialect;
use Phenix\Database\Dialects\PostgreSQL\Compilers\PostgresDeleteCompiler;
use Phenix\Database\Dialects\PostgreSQL\Compilers\PostgresExistsCompiler;
use Phenix\Database\Dialects\PostgreSQL\Compilers\PostgresInsertCompiler;
use Phenix\Database\Dialects\PostgreSQL\Compilers\PostgresSelectCompiler;
use Phenix\Database\Dialects\PostgreSQL\Compilers\PostgresUpdateCompiler;
use Phenix\Database\QueryAst;

final class PostgresDialect implements Dialect
{
    private PostgresSelectCompiler $selectCompiler;
    private PostgresInsertCompiler $insertCompiler;
    private PostgresUpdateCompiler $updateCompiler;
    private PostgresDeleteCompiler $deleteCompiler;
    private PostgresExistsCompiler $existsCompiler;

    public function __construct()
    {
        $this->selectCompiler = new PostgresSelectCompiler();
        $this->insertCompiler = new PostgresInsertCompiler();
        $this->updateCompiler = new PostgresUpdateCompiler();
        $this->deleteCompiler = new PostgresDeleteCompiler();
        $this->existsCompiler = new PostgresExistsCompiler();
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
