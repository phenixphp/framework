<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Json;
use Phenix\Database\Migrations\Columns\JsonB;

trait WithJson
{
    public function json(string $name): Json
    {
        return $this->addColumnWithAdapter(new Json($name));
    }

    public function jsonb(string $name): JsonB
    {
        return $this->addColumnWithAdapter(new JsonB($name));
    }
}
