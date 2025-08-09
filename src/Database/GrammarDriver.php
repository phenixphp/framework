<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Constants\Driver;

abstract class GrammarDriver
{
    protected Driver $driver;

    public function setDriver(Driver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }
}
