<?php

declare(strict_types=1);

namespace Phenix\Database\ORM;

abstract class Ashes
{
    protected string $table;

    public function getTable(): string
    {
        return $this->table;
    }
}
