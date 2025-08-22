<?php

declare(strict_types=1);

namespace Phenix\Database\Contracts;

interface Builder
{
    public function toSql(): array;
}
