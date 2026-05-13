<?php

declare(strict_types=1);

namespace Phenix\Database\Concerns;

use Phenix\Database\Constants\Driver;

trait HasDriver
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
