<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Boolean;
use Phenix\Database\Migrations\Columns\Enum;
use Phenix\Database\Migrations\Columns\Set;
use Phenix\Database\Migrations\Columns\Uuid;

trait WithSpecial
{
    public function boolean(string $name): Boolean
    {
        return $this->addColumnWithAdapter(new Boolean($name));
    }

    public function uuid(string $name): Uuid
    {
        return $this->addColumnWithAdapter(new Uuid($name));
    }

    public function enum(string $name, array $values): Enum
    {
        return $this->addColumnWithAdapter(new Enum($name, $values));
    }

    public function set(string $name, array $values): Set
    {
        return $this->addColumnWithAdapter(new Set($name, $values));
    }
}
