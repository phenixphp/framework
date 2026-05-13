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
    protected SelectCompiler $selectCompiler;

    protected InsertCompiler $insertCompiler;

    protected UpdateCompiler $updateCompiler;

    protected DeleteCompiler $deleteCompiler;

    protected ExistsCompiler $existsCompiler;

    abstract protected function initializeCompilers(): void;

    public function compile(QueryAst $ast, SqlMode $sqlMode = SqlMode::Prepared): array
    {
        return match ($ast->action) {
            Action::SELECT => $this->compileSelect($ast, $sqlMode),
            Action::INSERT => $this->compileInsert($ast, $sqlMode),
            Action::UPDATE => $this->compileUpdate($ast, $sqlMode),
            Action::DELETE => $this->compileDelete($ast, $sqlMode),
            Action::EXISTS => $this->compileExists($ast, $sqlMode),
        };
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileSelect(QueryAst $ast, SqlMode $sqlMode): array
    {
        $compiled = $this->selectCompiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileInsert(QueryAst $ast, SqlMode $sqlMode): array
    {
        $compiled = $this->insertCompiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileUpdate(QueryAst $ast, SqlMode $sqlMode): array
    {
        $compiled = $this->updateCompiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileDelete(QueryAst $ast, SqlMode $sqlMode): array
    {
        $compiled = $this->deleteCompiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function compileExists(QueryAst $ast, SqlMode $sqlMode): array
    {
        $compiled = $this->existsCompiler
            ->setAst($ast)
            ->setSqlMode($sqlMode)
            ->compile();

        return [$compiled->sql, $compiled->params];
    }
}
