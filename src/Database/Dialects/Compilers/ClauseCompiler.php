<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

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
