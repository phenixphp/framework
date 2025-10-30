<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations;

use Phenix\Database\Migrations\Columns\BigInteger;
use Phenix\Database\Migrations\Columns\Binary;
use Phenix\Database\Migrations\Columns\Boolean;
use Phenix\Database\Migrations\Columns\Column;
use Phenix\Database\Migrations\Columns\Date;
use Phenix\Database\Migrations\Columns\DateTime;
use Phenix\Database\Migrations\Columns\Decimal;
use Phenix\Database\Migrations\Columns\Enum;
use Phenix\Database\Migrations\Columns\Floating;
use Phenix\Database\Migrations\Columns\Integer;
use Phenix\Database\Migrations\Columns\Json;
use Phenix\Database\Migrations\Columns\SmallInteger;
use Phenix\Database\Migrations\Columns\Str;
use Phenix\Database\Migrations\Columns\Text;
use Phenix\Database\Migrations\Columns\Timestamp;
use Phenix\Database\Migrations\Columns\UnsignedBigInteger;
use Phenix\Database\Migrations\Columns\UnsignedDecimal;
use Phenix\Database\Migrations\Columns\UnsignedFloat;
use Phenix\Database\Migrations\Columns\UnsignedInteger;
use Phenix\Database\Migrations\Columns\UnsignedSmallInteger;
use Phenix\Database\Migrations\Columns\Uuid;
use Phinx\Db\Table as PhinxTable;

class Table extends PhinxTable
{
    /**
     * @var array<Column>
     */
    protected array $columns = [];

    public function getColumnBuilders(): array
    {
        return $this->columns;
    }

    public function string(string $name, int $limit = 255): Str
    {
        return $this->addColumnWithAdapter(new Str($name, $limit));
    }

    public function integer(string $name, int|null $limit = null, bool $identity = false): Integer
    {
        return $this->addColumnWithAdapter(new Integer($name, $limit, $identity));
    }

    public function bigInteger(string $name, bool $identity = false): BigInteger
    {
        return $this->addColumnWithAdapter(new BigInteger($name, $identity));
    }

    public function unsignedInteger(string $name, int|null $limit = null, bool $identity = false): UnsignedInteger
    {
        return $this->addColumnWithAdapter(new UnsignedInteger($name, $limit, $identity));
    }

    public function unsignedBigInteger(string $name, bool $identity = false): UnsignedBigInteger
    {
        return $this->addColumnWithAdapter(new UnsignedBigInteger($name, $identity));
    }

    public function smallInteger(string $name, bool $identity = false): SmallInteger
    {
        return $this->addColumnWithAdapter(new SmallInteger($name, $identity));
    }

    public function text(string $name, int|null $limit = null): Text
    {
        return $this->addColumnWithAdapter(new Text($name, $limit));
    }

    public function boolean(string $name): Boolean
    {
        return $this->addColumnWithAdapter(new Boolean($name));
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): Decimal
    {
        return $this->addColumnWithAdapter(new Decimal($name, $precision, $scale));
    }

    public function unsignedDecimal(string $name, int $precision = 10, int $scale = 2): UnsignedDecimal
    {
        return $this->addColumnWithAdapter(new UnsignedDecimal($name, $precision, $scale));
    }

    public function unsignedSmallInteger(string $name, bool $identity = false): UnsignedSmallInteger
    {
        return $this->addColumnWithAdapter(new UnsignedSmallInteger($name, $identity));
    }

    public function unsignedFloat(string $name): UnsignedFloat
    {
        return $this->addColumnWithAdapter(new UnsignedFloat($name));
    }

    public function dateTime(string $name): DateTime
    {
        return $this->addColumnWithAdapter(new DateTime($name));
    }

    public function timestamp(string $name, bool $timezone = false): Timestamp
    {
        return $this->addColumnWithAdapter(new Timestamp($name, $timezone));
    }

    public function json(string $name): Json
    {
        return $this->addColumnWithAdapter(new Json($name));
    }

    public function uuid(string $name): Uuid
    {
        return $this->addColumnWithAdapter(new Uuid($name));
    }

    public function enum(string $name, array $values): Enum
    {
        return $this->addColumnWithAdapter(new Enum($name, $values));
    }

    public function float(string $name): Floating
    {
        return $this->addColumnWithAdapter(new Floating($name));
    }

    public function date(string $name): Date
    {
        return $this->addColumnWithAdapter(new Date($name));
    }

    public function binary(string $name, int|null $limit = null): Binary
    {
        return $this->addColumnWithAdapter(new Binary($name, $limit));
    }

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

    public function __destruct()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        $this->save();
    }

    /**
     * @template T of Column
     * @param T $column
     * @return T
     */
    private function addColumnWithAdapter(Column $column): Column
    {
        $column->setAdapter($this->getAdapter());

        $this->columns[] = $column;

        return $column;
    }
}
