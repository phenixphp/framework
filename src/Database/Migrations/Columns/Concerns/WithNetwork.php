<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\Cidr;
use Phenix\Database\Migrations\Columns\Inet;
use Phenix\Database\Migrations\Columns\MacAddr;

trait WithNetwork
{
    public function inet(string $name): Inet
    {
        return $this->addColumnWithAdapter(new Inet($name));
    }

    public function cidr(string $name): Cidr
    {
        return $this->addColumnWithAdapter(new Cidr($name));
    }

    public function macaddr(string $name): MacAddr
    {
        return $this->addColumnWithAdapter(new MacAddr($name));
    }
}
