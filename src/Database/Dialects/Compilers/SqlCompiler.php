<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Constants\SqlMark;
use Phenix\Database\Contracts\SqlCompiler as SqlCompilerContract;
use Phenix\Database\Dialects\SqlData;
use Phenix\Database\QueryAst;
use Phenix\Database\Subquery;
use Phenix\Database\Wrapper;

abstract class SqlCompiler implements SqlCompilerContract
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

    protected function replacePlaceholders(string $sql): string
    {
        return str_replace(SqlMark::Placeholder->value, '?', $sql);
    }

    protected function wrap(string $value): string
    {
        return Wrapper::column($this->ast->driver, $value);
    }

    protected function wrapOf(string $value): string
    {
        return (string) Wrapper::of($this->ast->driver, $value);
    }

    protected function compileTable(): SqlData
    {
        if ($this->ast->table instanceof Subquery) {
            $this->ast->table->setDriver($this->ast->driver);

            [$sql, $params] = $this->ast->table->toSql();

            return new SqlData($sql, $params);
        }

        return new SqlData($this->wrapOf($this->ast->table));
    }

    protected function wrapList(array $values): array
    {
        return Wrapper::columnList($this->ast->driver, $values);
    }
}
