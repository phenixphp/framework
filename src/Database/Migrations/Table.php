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
use Phenix\Database\Migrations\Columns\UnsignedInteger;
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
        $column = new Str($name, $limit);

        $this->columns[] = $column;

        return $column;
    }

    public function integer(string $name, int|null $limit = null, bool $identity = false): Integer
    {
        $column = new Integer($name, $limit, $identity);

        $this->columns[] = $column;

        return $column;
    }

    public function bigInteger(string $name, bool $identity = false): BigInteger
    {
        $column = new BigInteger($name, $identity);

        $this->columns[] = $column;

        return $column;
    }

    public function unsignedInteger(string $name, int|null $limit = null, bool $identity = false): UnsignedInteger
    {
        $column = new UnsignedInteger($name, $limit, $identity);

        $this->columns[] = $column;

        return $column;
    }

    public function unsignedBigInteger(string $name, bool $identity = false): UnsignedBigInteger
    {
        $column = new UnsignedBigInteger($name, $identity);

        $this->columns[] = $column;

        return $column;
    }

    public function smallInteger(string $name, bool $identity = false): SmallInteger
    {
        $column = new SmallInteger($name, $identity);

        $this->columns[] = $column;

        return $column;
    }

    public function text(string $name, int|null $limit = null): Text
    {
        $column = new Text($name, $limit);

        $this->columns[] = $column;

        return $column;
    }

    public function boolean(string $name): Boolean
    {
        $column = new Boolean($name);

        $this->columns[] = $column;

        return $column;
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): Decimal
    {
        $column = new Decimal($name, $precision, $scale);

        $this->columns[] = $column;

        return $column;
    }

    public function dateTime(string $name): DateTime
    {
        $column = new DateTime($name);

        $this->columns[] = $column;

        return $column;
    }

    public function timestamp(string $name, bool $timezone = false): Timestamp
    {
        $column = new Timestamp($name, $timezone);

        $this->columns[] = $column;

        return $column;
    }

    public function json(string $name): Json
    {
        $column = new Json($name);

        $this->columns[] = $column;

        return $column;
    }

    public function uuid(string $name): Uuid
    {
        $column = new Uuid($name);

        $this->columns[] = $column;

        return $column;
    }

    public function enum(string $name, array $values): Enum
    {
        $column = new Enum($name, $values);

        $this->columns[] = $column;

        return $column;
    }

    public function float(string $name): Floating
    {
        $column = new Floating($name);

        $this->columns[] = $column;

        return $column;
    }

    public function date(string $name): Date
    {
        $column = new Date($name);

        $this->columns[] = $column;

        return $column;
    }

    public function binary(string $name, int|null $limit = null): Binary
    {
        $column = new Binary($name, $limit);

        $this->columns[] = $column;

        return $column;
    }

    public function id(string $name = 'id'): UnsignedInteger
    {
        $column = new UnsignedInteger($name, null, true);

        $this->columns[] = $column;

        return $column;
    }

    public function timestamps(bool $timezone = false): self
    {
        $createdAt = new Timestamp('created_at', $timezone);
        $createdAt->nullable()->currentTimestamp();
        $this->columns[] = $createdAt;

        $updatedAt = new Timestamp('updated_at', $timezone);
        $updatedAt->nullable()->onUpdateCurrentTimestamp();
        $this->columns[] = $updatedAt;

        return $this;
    }

    public function __destruct()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        $this->save();
    }
}
