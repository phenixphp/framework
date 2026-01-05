<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects;

use Phenix\Database\Constants\Action;
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
