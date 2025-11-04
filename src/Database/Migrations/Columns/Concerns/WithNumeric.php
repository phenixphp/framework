<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns\Concerns;

use Phenix\Database\Migrations\Columns\BigInteger;
use Phenix\Database\Migrations\Columns\Decimal;
use Phenix\Database\Migrations\Columns\Double;
use Phenix\Database\Migrations\Columns\Floating;
use Phenix\Database\Migrations\Columns\Integer;
use Phenix\Database\Migrations\Columns\SmallInteger;
use Phenix\Database\Migrations\Columns\UnsignedBigInteger;
use Phenix\Database\Migrations\Columns\UnsignedDecimal;
use Phenix\Database\Migrations\Columns\UnsignedFloat;
use Phenix\Database\Migrations\Columns\UnsignedInteger;
use Phenix\Database\Migrations\Columns\UnsignedSmallInteger;

trait WithNumeric
{
    public function integer(string $name, int|null $limit = null, bool $identity = false): Integer
    {
        return $this->addColumnWithAdapter(new Integer($name, $limit, $identity));
    }

    public function bigInteger(string $name, bool $identity = false): BigInteger
    {
        return $this->addColumnWithAdapter(new BigInteger($name, $identity));
    }

    public function smallInteger(string $name, bool $identity = false): SmallInteger
    {
        return $this->addColumnWithAdapter(new SmallInteger($name, $identity));
    }

    public function unsignedInteger(string $name, int|null $limit = null, bool $identity = false): UnsignedInteger
    {
        return $this->addColumnWithAdapter(new UnsignedInteger($name, $limit, $identity));
    }

    public function unsignedBigInteger(string $name, bool $identity = false): UnsignedBigInteger
    {
        return $this->addColumnWithAdapter(new UnsignedBigInteger($name, $identity));
    }

    public function unsignedSmallInteger(string $name, bool $identity = false): UnsignedSmallInteger
    {
        return $this->addColumnWithAdapter(new UnsignedSmallInteger($name, $identity));
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): Decimal
    {
        return $this->addColumnWithAdapter(new Decimal($name, $precision, $scale));
    }

    public function unsignedDecimal(string $name, int $precision = 10, int $scale = 2): UnsignedDecimal
    {
        return $this->addColumnWithAdapter(new UnsignedDecimal($name, $precision, $scale));
    }

    public function float(string $name): Floating
    {
        return $this->addColumnWithAdapter(new Floating($name));
    }

    public function unsignedFloat(string $name): UnsignedFloat
    {
        return $this->addColumnWithAdapter(new UnsignedFloat($name));
    }

    public function double(string $name, bool $signed = true): Double
    {
        return $this->addColumnWithAdapter(new Double($name, $signed));
    }
}
