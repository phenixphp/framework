<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\MySQL;

use Phenix\Database\Constants\Action;
use Phenix\Database\Dialects\Contracts\Dialect;
use Phenix\Database\Dialects\Contracts\DialectCapabilities;
use Phenix\Database\Dialects\MySQL\Compilers\MysqlSelectCompiler;
use Phenix\Database\Dialects\MySQL\Compilers\MysqlInsertCompiler;
use Phenix\Database\Dialects\MySQL\Compilers\MysqlUpdateCompiler;
use Phenix\Database\Dialects\MySQL\Compilers\MysqlDeleteCompiler;
use Phenix\Database\Dialects\MySQL\Compilers\MysqlExistsCompiler;
use Phenix\Database\QueryAst;

final class MysqlDialect implements Dialect
{
    private DialectCapabilities $capabilities;

    private MysqlSelectCompiler $selectCompiler;

    private MysqlInsertCompiler $insertCompiler;

    private MysqlUpdateCompiler $updateCompiler;

    private MysqlDeleteCompiler $deleteCompiler;

    private MysqlExistsCompiler $existsCompiler;

    public function __construct()
    {
        $this->capabilities = new DialectCapabilities(
            supportsLocks: true,
            supportsUpsert: true,
            supportsReturning: false,
            supportsJsonOperators: true,
            supportsAdvancedLocks: false,
            supportsInsertIgnore: true,
            supportsFulltextSearch: true,
            supportsGeneratedColumns: true,
        );

        $this->selectCompiler = new MysqlSelectCompiler();
        $this->insertCompiler = new MysqlInsertCompiler();
        $this->updateCompiler = new MysqlUpdateCompiler();
        $this->deleteCompiler = new MysqlDeleteCompiler();
        $this->existsCompiler = new MysqlExistsCompiler();
    }

    public function capabilities(): DialectCapabilities
    {
        return $this->capabilities;
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
