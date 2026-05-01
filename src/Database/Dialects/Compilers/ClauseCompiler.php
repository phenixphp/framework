<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use LogicException;
use Phenix\Database\Contracts\ClauseCompiler as ClauseCompilerContract;
use Phenix\Database\QueryAst;
use Phenix\Database\Wrapper;

abstract class ClauseCompiler implements ClauseCompilerContract
{
    protected QueryAst $ast;

    protected WhereCompiler $whereCompiler;

    protected JoinCompiler $joinCompiler;

    protected HavingCompiler $havingCompiler;

    public function setAst(QueryAst $ast): static
    {
        $this->ast = $ast;

        return $this;
    }

    protected function ast(): QueryAst
    {
        if (! isset($this->ast)) {
            throw new LogicException('Query AST must be set before compiling.');
        }

        return $this->ast;
    }

    protected function wrap(string $value): string
    {
        return Wrapper::column($this->ast->driver, $value);
    }

    protected function wrapOf(string $value): string
    {
        return (string) Wrapper::of($this->ast->driver, $value);
    }

    protected function wrapList(array $values): array
    {
        return Wrapper::columnList($this->ast->driver, $values);
    }
}
