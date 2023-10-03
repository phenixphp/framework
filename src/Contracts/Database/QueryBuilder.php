<?php

declare(strict_types=1);

namespace Phenix\Contracts\Database;

interface QueryBuilder
{
    public function select(array $fields): self;
}
