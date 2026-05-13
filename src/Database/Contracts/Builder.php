<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

use Phenix\Database\Constants\SqlMode;

interface Builder
{
    public function toSql(SqlMode $sqlMode = SqlMode::Prepared): array;
}
