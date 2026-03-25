<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Binary;
use Phenix\Database\Migrations\Columns\Bit;
use Phenix\Database\Migrations\Columns\Blob;

trait WithBinary
{
    public function binary(string $name, int|null $limit = null): Binary
    {
        return $this->addColumnWithAdapter(new Binary($name, $limit));
    }

    public function blob(string $name, int|null $limit = null): Blob
    {
        return $this->addColumnWithAdapter(new Blob($name, $limit));
    }

    public function bit(string $name, int $limit = 1): Bit
    {
        return $this->addColumnWithAdapter(new Bit($name, $limit));
    }
}
