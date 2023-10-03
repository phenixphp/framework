<?php

declare(strict_types=1);

namespace Phenix\Contracts\Database;

interface Builder
{
    public function toSql(): array;
}
