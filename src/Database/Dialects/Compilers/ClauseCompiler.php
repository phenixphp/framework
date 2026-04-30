<?php

declare(strict_types=1);

namespace Phenix\Database\Dialects\Compilers;

use Phenix\Database\Contracts\ClauseCompiler as ClauseCompilerContract;

abstract class ClauseCompiler implements ClauseCompilerContract
{
    protected WhereCompiler $whereCompiler;

    protected JoinCompiler $joinCompiler;

    protected HavingCompiler $havingCompiler;
}
