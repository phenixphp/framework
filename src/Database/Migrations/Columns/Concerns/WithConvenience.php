<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Timestamp;
use Phenix\Database\Migrations\Columns\UnsignedInteger;

trait WithConvenience
{
    public function id(string $name = 'id'): UnsignedInteger
    {
        return $this->addColumnWithAdapter(new UnsignedInteger($name, null, true));
    }

    public function timestamps(bool $timezone = false): self
    {
        $createdAt = $this->addColumnWithAdapter(new Timestamp('created_at', $timezone));
        $createdAt->nullable()->currentTimestamp();

        $updatedAt = $this->addColumnWithAdapter(new Timestamp('updated_at', $timezone));
        $updatedAt->nullable()->onUpdateCurrentTimestamp();

        return $this;
    }
}
